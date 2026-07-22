<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contract;
use App\Models\Room;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Events\ContractSignedEvent;
use App\Models\RoomPost;
use Inertia\Inertia;

class ContractController extends Controller
{
    /**
     * Tra cứu tài khoản người thuê theo SĐT hoặc Email
     */
    public function searchTenant(Request $request)
    {
        $query = trim($request->get('q', ''));
        if (empty($query)) {
            return response()->json([]);
        }

        $users = User::where(function($q) use ($query) {
                $q->where('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'phone', 'email', 'cccd_number')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    /**
     * Show the form to create a draft contract (Giai đoạn 3)
     */
    public function createDraft(Request $request)
    {
        // Require appointment_id to auto-fill guest info
        $appointmentId = $request->get('appointment_id');
        $appointment = Appointment::with(['user', 'room'])->findOrFail($appointmentId);

        // Ensure landlord owns the room
        if ($appointment->landlord_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('Landlord/Contracts/Create', [
            'appointment' => $appointment
        ]);
    }

    /**
     * Tự tạo hợp đồng lưu nháp hoặc tải ảnh lên để tạo hợp đồng PDF trực tiếp
     */
    public function storeDraftAndExport(Request $request)
    {
        $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'room_id' => 'nullable|exists:rooms,id',
            'tenant_id' => 'nullable|exists:users,id',
            'tenant_name' => 'required_without:appointment_id|nullable|string|max:255',
            'tenant_phone' => 'required_without:appointment_id|nullable|string|max:20',
            'tenant_email' => 'nullable|email|max:255',
            'tenant_cccd' => 'nullable|string|max:20',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'monthly_rent' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'billing_cycle' => 'nullable|integer|min:1',
            'signed_image' => 'nullable|array',
            'signed_image.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $roomId = null;
        $tenantId = null;

        // Nếu tạo từ Lịch hẹn
        if ($request->filled('appointment_id')) {
            $appointment = Appointment::findOrFail($request->appointment_id);
            if ($appointment->landlord_id !== Auth::id()) {
                abort(403);
            }
            $roomId = $appointment->room_id;
            $tenantId = $appointment->user_id;

            // Cập nhật trạng thái lịch hẹn thành 'success_matched' để đánh dấu đã thành công ký HĐ
            $appointment->update(['status' => 'success_matched']);

            if ($request->filled('tenant_cccd') && $appointment->user) {
                $appointment->user->update(['cccd_number' => $request->tenant_cccd]);
            }
        } else {
            // Nếu tạo chọn phòng trực tiếp
            $roomId = $request->room_id;
            if (!$roomId) {
                return redirect()->back()->with('error', 'Vui lòng chọn phòng trọ.');
            }

            // Kiểm tra phòng thuộc quyền sở hữu của chủ trọ
            $room = Room::with('boardingHouse')->findOrFail($roomId);
            if ($room->boardingHouse->user_id !== Auth::id()) {
                abort(403, 'Bạn không có quyền quản lý phòng này.');
            }

            // Xử lý xác định hoặc tạo người thuê
            if ($request->filled('tenant_id')) {
                $tenantId = $request->tenant_id;
                $user = User::find($tenantId);
                if ($user && $request->filled('tenant_cccd')) {
                    $user->update(['cccd_number' => $request->tenant_cccd]);
                }
            } else {
                // Tra cứu theo SĐT hoặc Email
                $user = User::where('phone', $request->tenant_phone)
                    ->orWhere(function($q) use ($request) {
                        if ($request->filled('tenant_email')) {
                            $q->where('email', $request->tenant_email);
                        }
                    })
                    ->first();

                if ($user) {
                    $tenantId = $user->id;
                    if ($request->filled('tenant_cccd')) {
                        $user->update(['cccd_number' => $request->tenant_cccd]);
                    }
                } else {
                    // Tự tạo tài khoản Tenant mới nếu chưa tồn tại
                    $email = $request->tenant_email ?: ($request->tenant_phone . '@ninhbinhhomestay.local');
                    $newUser = User::create([
                        'name' => $request->tenant_name,
                        'phone' => $request->tenant_phone,
                        'email' => $email,
                        'password' => Hash::make('12345678'),
                        'role' => 'tenant',
                        'cccd_number' => $request->tenant_cccd,
                    ]);
                    $tenantId = $newUser->id;
                }
            }
        }

        $startDate = $request->filled('start_date') ? $request->start_date : now()->toDateString();
        $endDate = $request->filled('end_date') ? $request->end_date : now()->addYear()->toDateString();
        $billingCycle = $request->filled('billing_cycle') ? $request->billing_cycle : 1;

        // Tạo bản ghi hợp đồng
        $contract = Contract::create([
            'tenant_id' => $tenantId,
            'room_id' => $roomId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'monthly_rent' => $request->monthly_rent,
            'deposit_amount' => $request->deposit ?? 0,
            'billing_cycle' => $billingCycle,
            'status' => 'awaiting_upload',
        ]);

        // Tự động chuyển trạng thái phòng sang Đã đặt cọc và ẩn tin đăng
        $room = Room::find($roomId);
        if ($room) {
            $room->update([
                'status' => 'deposited',
                'current_people' => min($room->capacity, $room->current_people + 1)
            ]);
            RoomPost::where('room_id', $room->id)->update(['status' => 'hidden']);
        }

        // Nếu chủ trọ upload ảnh ký tay luôn
        if ($request->hasFile('signed_image')) {
            $paths = [];
            foreach ($request->file('signed_image') as $file) {
                $paths[] = $file->store('contracts/signed', 'public');
            }
            
            // Generate PDF từ ảnh
            $pdf = Pdf::loadView('pdf.images_to_pdf', ['images' => $paths]);
            $fileName = 'contracts/contract_' . $contract->id . '_' . time() . '.pdf';
            Storage::disk('public')->put($fileName, $pdf->output());

            $contract->update([
                'signed_contract_image' => json_encode($paths),
                'contract_file_path' => $fileName,
                'status' => 'active',
            ]);

            // Kích hoạt hợp đồng
            event(new ContractSignedEvent($contract));
            
            return redirect()->back()->with('success', 'Hợp đồng đã được tạo và kích hoạt thành công!');
        }

        // Gửi thông báo cho khách thuê nếu chỉ tạo nháp
        if ($tenantId) {
            $tenantUser = User::find($tenantId);
            if ($tenantUser) {
                $tenantUser->notify(new \App\Notifications\ContractCreatedNotification($contract));
            }
        }

        return redirect()->back()->with('success', 'Đã lưu thông tin hợp đồng. Vui lòng tải ảnh lên sau để kích hoạt.');
    }

    /**
     * Upload signed original contract image
     */
    public function uploadSignedContract(Request $request, Contract $contract)
    {
        // Ensure landlord owns the contract's room
        if ($contract->room->boardingHouse->user_id !== Auth::id()) {
             abort(403);
        }

        $request->validate([
            'signed_image' => 'required|array|min:1',
            'signed_image.*' => 'image|mimes:jpeg,png,jpg|max:5120', // max 5MB per image
        ]);

        if ($request->hasFile('signed_image')) {
            $paths = [];
            foreach ($request->file('signed_image') as $file) {
                $paths[] = $file->store('contracts/signed', 'public');
            }
            
            // Generate PDF từ ảnh
            $pdf = Pdf::loadView('pdf.images_to_pdf', ['images' => $paths]);
            $fileName = 'contracts/contract_' . $contract->id . '_' . time() . '.pdf';
            Storage::disk('public')->put($fileName, $pdf->output());

            $contract->update([
                'signed_contract_image' => json_encode($paths),
                'contract_file_path' => $fileName,
            ]);

            // Fire event to activate contract and room
            event(new ContractSignedEvent($contract));

            // Ẩn tin đăng phòng
            RoomPost::where('room_id', $contract->room_id)->update(['status' => 'hidden']);

            return redirect()->back()->with('success', 'Hợp đồng đã được tải lên và kích hoạt thành công!');
        }

        return redirect()->back()->with('error', 'Không tìm thấy file ảnh.');
    }

    /**
     * Gia hạn hợp đồng
     */
    public function extendContract(Request $request, Contract $contract)
    {
        if ($contract->room->boardingHouse->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'new_end_date' => 'required|date|after:' . $contract->end_date,
        ]);

        $contract->update([
            'end_date' => $request->new_end_date,
        ]);

        return redirect()->back()->with('success', 'Đã gia hạn hợp đồng thành công đến ' . date('d/m/Y', strtotime($request->new_end_date)));
    }

    /**
     * Hủy/Thanh lý hợp đồng
     */
    public function terminateContract(Contract $contract)
    {
        if ($contract->room->boardingHouse->user_id !== Auth::id()) {
            abort(403);
        }

        $contract->update([
            'status' => 'cancelled'
        ]);

        $room = $contract->room;
        if ($room) {
            $room->update([
                'status' => 'available',
                'current_people' => max(0, $room->current_people - 1)
            ]);

            // Khôi phục tin đăng về nháp
            RoomPost::where('room_id', $room->id)->update(['status' => 'draft']);
        }

        return redirect()->back()->with('success', 'Hợp đồng đã được hủy và bài đăng phòng đã được chuyển về dạng Nháp.');
    }
}
