<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contract;
use App\Models\Room;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\ContractSignedEvent;
use App\Models\RoomPost;
use Inertia\Inertia;

class ContractController extends Controller
{
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
            'appointment_id' => 'required|exists:appointments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'tenant_cccd' => 'nullable|string|max:20',
            'billing_cycle' => 'nullable|integer|min:1',
            'signed_image' => 'nullable|array',
            'signed_image.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);

        if ($appointment->landlord_id !== Auth::id()) {
            abort(403);
        }

        // Cập nhật CCCD của khách thuê nếu có
        if ($request->filled('tenant_cccd')) {
            $user = $appointment->user;
            if ($user) {
                $user->update(['cccd_number' => $request->tenant_cccd]);
            }
        }

        // Create contract (status is awaiting_upload by default)
        $contract = Contract::create([
            'tenant_id' => $appointment->user_id,
            'room_id' => $appointment->room_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'monthly_rent' => $request->monthly_rent,
            'deposit_amount' => $request->deposit ?? 0,
            'status' => 'awaiting_upload',
        ]);

        // Tự động chuyển trạng thái phòng sang Đã đặt cọc và ẩn tin đăng
        $room = $appointment->room;
        if ($room) {
            $room->update([
                'status' => 'deposited',
                'current_people' => min($room->capacity, $room->current_people + 1)
            ]);
            
            // Ẩn tin đăng phòng
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
            ]);

            // Kích hoạt hợp đồng
            event(new ContractSignedEvent($contract));
            
            return redirect()->back()->with('success', 'Hợp đồng đã được tạo và kích hoạt thành công!');
        }

        // Gửi thông báo cho khách thuê nếu chỉ tạo nháp
        if ($appointment->user) {
            $appointment->user->notify(new \App\Notifications\ContractCreatedNotification($contract));
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
