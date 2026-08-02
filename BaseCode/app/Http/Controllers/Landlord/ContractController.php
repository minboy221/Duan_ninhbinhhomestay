<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contract;
use App\Models\ContractExtension;
use App\Models\Room;
use App\Models\User;
use App\Models\RoomPost;
use App\Services\ContractOcrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Events\ContractSignedEvent;
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
     * Show the form to create a draft contract
     */
    public function createDraft(Request $request)
    {
        $appointmentId = $request->get('appointment_id');
        $appointment = Appointment::with(['user', 'room'])->findOrFail($appointmentId);

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
            'number_of_tenants' => 'nullable|integer|min:1|max:20',
            'billing_cycle' => 'nullable|integer|min:1',
            'signed_image' => 'nullable|array',
            'signed_image.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'terms_accepted' => 'nullable|boolean',
        ]);

        $roomId = null;
        $tenantId = null;

        if ($request->filled('appointment_id')) {
            $appointment = Appointment::findOrFail($request->appointment_id);
            if ($appointment->landlord_id !== Auth::id()) {
                abort(403);
            }
            $roomId = $appointment->room_id;
            $tenantId = $appointment->user_id;

            $appointment->update(['status' => 'success_matched']);

            if ($request->filled('tenant_cccd') && $appointment->user) {
                $appointment->user->update(['cccd_number' => $request->tenant_cccd]);
            }
        } else {
            $roomId = $request->room_id;
            if (!$roomId) {
                return redirect()->back()->with('error', 'Vui lòng chọn phòng trọ.');
            }

            $room = Room::with('boardingHouse')->findOrFail($roomId);
            if ($room->boardingHouse->user_id !== Auth::id()) {
                abort(403, 'Bạn không có quyền quản lý phòng này.');
            }

            if ($request->filled('tenant_id')) {
                $tenantId = $request->tenant_id;
                $user = User::find($tenantId);
                if ($user && $request->filled('tenant_cccd')) {
                    $user->update(['cccd_number' => $request->tenant_cccd]);
                }
            } else {
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

        // RÀNG BUỘC PHÁP LÝ: Mỗi người dùng chỉ được phép sở hữu tối đa 1 hợp đồng đang có hiệu lực trong hệ thống
        if ($tenantId) {
            $existingActiveContract = Contract::where('tenant_id', $tenantId)
                ->whereIn('status', ['active', 'signed', 'pending', 'awaiting_upload', 'termination_requested', 'expiring'])
                ->with('room')
                ->first();

            if ($existingActiveContract) {
                $roomNum = $existingActiveContract->room->room_number ?? 'chưa xác định';
                return redirect()->back()->with('error', 'Khách thuê này hiện đã có 1 hợp đồng thuê trọ đang có hiệu lực trong hệ thống (Phòng ' . $roomNum . '). Hệ thống quy định mỗi người dùng chỉ được có tối đa 1 hợp đồng tại cùng một thời điểm.');
            }
        }

        $startDate = $request->filled('start_date') ? $request->start_date : now()->toDateString();
        $endDate = $request->filled('end_date') ? $request->end_date : now()->addYear()->toDateString();
        $billingCycle = $request->filled('billing_cycle') ? $request->billing_cycle : 1;
        $numberOfTenants = (int)$request->input('number_of_tenants', 1);

        $room = Room::find($roomId);
        if ($room) {
            $availableCapacity = max(1, $room->capacity - $room->current_people);
            if ($numberOfTenants > $availableCapacity) {
                return redirect()->back()->with('error', "Số lượng người ở ({$numberOfTenants} người) vượt quá sức chứa còn lại của phòng (Sức chứa tối đa {$room->capacity} người, hiện đã có {$room->current_people} người, chỉ còn trống {$availableCapacity} chỗ).");
            }
        }

        $contract = Contract::create([
            'tenant_id' => $tenantId,
            'room_id' => $roomId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'monthly_rent' => $request->monthly_rent,
            'deposit_amount' => $request->deposit ?? 0,
            'number_of_tenants' => $numberOfTenants,
            'billing_cycle' => $billingCycle,
            'status' => 'awaiting_upload',
            'terms_accepted' => $request->boolean('terms_accepted', true),
            'terms_accepted_at' => now(),
            'ocr_status' => 'pending',
        ]);

        $room = Room::find($roomId);
        if ($room) {
            $room->update([
                'status' => 'deposited',
                'current_people' => min($room->capacity, max(1, $room->current_people + $numberOfTenants))
            ]);
            RoomPost::where('room_id', $room->id)->update(['status' => 'hidden']);
        }

        // Nếu tải ảnh ký tay luôn
        if ($request->hasFile('signed_image')) {
            return $this->uploadSignedContract($request, $contract);
        }

        if ($tenantId) {
            $tenantUser = User::find($tenantId);
            if ($tenantUser) {
                $tenantUser->notify(new \App\Notifications\ContractCreatedNotification($contract));
            }
        }

        return redirect()->back()->with('success', 'Đã lưu thông tin hợp đồng. Vui lòng tải ảnh hợp đồng đã điền & ký lên để kích hoạt.');
    }

    /**
     * Upload signed original contract image with OCR Validation
     */
    public function uploadSignedContract(Request $request, Contract $contract)
    {
        if ($contract->room->boardingHouse->user_id !== Auth::id()) {
             abort(403);
        }

        $request->validate([
            'signed_image' => 'required|array|min:1',
            'signed_image.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($request->hasFile('signed_image')) {
            $paths = [];
            foreach ($request->file('signed_image') as $file) {
                $paths[] = $file->store('contracts/signed', 'public');
            }

            // 1. Kiểm duyệt OCR tính điền tay & chữ ký hợp lệ của ảnh hợp đồng
            $ocrResult = ContractOcrService::validateContractImages($paths, [
                'tenant_name' => $contract->tenant->name ?? '',
                'tenant_cccd' => $contract->tenant->cccd_number ?? '',
            ]);

            if (!$ocrResult['is_valid']) {
                $contract->update([
                    'ocr_status' => 'failed',
                    'ocr_rejection_reason' => $ocrResult['reason']
                ]);

                return redirect()->back()->with('error', $ocrResult['reason']);
            }

            // 2. OCR thành công -> Tạo PDF từ ảnh
            $pdf = Pdf::loadView('pdf.images_to_pdf', ['images' => $paths]);
            $fileName = 'contracts/contract_' . $contract->id . '_' . time() . '.pdf';
            Storage::disk('public')->put($fileName, $pdf->output());

            $contract->update([
                'signed_contract_image' => json_encode($paths),
                'contract_file_path' => $fileName,
                'status' => 'active',
                'signed_at' => now(),
                'ocr_status' => 'passed',
                'ocr_rejection_reason' => null,
            ]);

            event(new ContractSignedEvent($contract));
            RoomPost::where('room_id', $contract->room_id)->update(['status' => 'hidden']);

            return redirect()->back()->with('success', 'Hợp đồng đã được quét OCR xác minh và kích hoạt thành công!');
        }

        return redirect()->back()->with('error', 'Không tìm thấy file ảnh.');
    }

    /**
     * API quét OCR từ tệp ảnh tải lên và trả về dữ liệu trích xuất cho Form tạo hợp đồng
     */
    public function extractOcrData(Request $request)
    {
        $request->validate([
            'ocr_file' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($request->hasFile('ocr_file')) {
            $path = $request->file('ocr_file')->store('contracts/ocr_temp', 'public');
            $fullPath = storage_path('app/public/' . $path);
            if (!file_exists($fullPath)) {
                $fullPath = public_path('storage/' . $path);
            }

            $ocrData = ContractOcrService::extractContractFields($fullPath);
            return response()->json($ocrData);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy file ảnh để quét OCR.'
        ], 400);
    }

    /**
     * Hủy hợp đồng nháp (chưa kích hoạt)
     */
    public function cancelDraft(Request $request, Contract $contract)
    {
        if ($contract->room->boardingHouse->user_id !== Auth::id() && $contract->tenant_id !== Auth::id()) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }

        if (!in_array($contract->status, ['awaiting_upload', 'draft'])) {
            return redirect()->back()->with('error', 'Chỉ có thể hủy hợp đồng nháp chưa được kích hoạt.');
        }

        $contract->update([
            'status' => 'cancelled',
            'cancelled_by' => Auth::id(),
            'cancellation_reason' => $request->input('reason', 'Đã hủy hợp đồng nháp')
        ]);

        $room = $contract->room;
        if ($room) {
            $room->update([
                'status' => 'available',
                'current_people' => max(0, $room->current_people - 1)
            ]);

            RoomPost::where('room_id', $room->id)->update(['status' => 'active']);
        }

        return redirect()->back()->with('success', 'Đã hủy hợp đồng nháp và giải phóng phòng thành công!');
    }

    /**
     * Quét tự động trạng thái của tất cả hợp đồng (active -> expiring -> expired)
     */
    public static function scanContractStatuses($landlordId = null)
    {
        $today = now()->format('Y-m-d');
        $expiringThreshold = now()->addDays(30)->format('Y-m-d');

        $query = Contract::whereIn('status', ['active', 'expiring']);
        if ($landlordId) {
            $query->whereHas('room.boardingHouse', function($q) use ($landlordId) {
                $q->where('user_id', $landlordId);
            });
        }

        $contracts = $query->get();
        $updatedCount = 0;

        Contract::$allowImmutableUpdate = true;
        foreach ($contracts as $contract) {
            $endDateStr = $contract->end_date ? (is_string($contract->end_date) ? substr($contract->end_date, 0, 10) : $contract->end_date->format('Y-m-d')) : null;
            if (!$endDateStr) continue;

            if ($endDateStr <= $today && $contract->status !== 'expired') {
                $contract->update([
                    'status' => 'expired',
                    'cancellation_reason' => $contract->cancellation_reason ?: 'Hợp đồng tự động chuyển sang Hết hạn do đã đến ngày kết thúc.'
                ]);
                $updatedCount++;
            } elseif ($endDateStr <= $expiringThreshold && $endDateStr > $today && $contract->status === 'active') {
                $contract->update([
                    'status' => 'expiring'
                ]);
                $updatedCount++;
            }
        }
        Contract::$allowImmutableUpdate = false;

        return $updatedCount;
    }

    /**
     * Action thủ công trigger quét hợp đồng từ giao diện Chủ trọ
     */
    public function scanContracts(Request $request)
    {
        $landlordId = Auth::id();
        $count = self::scanContractStatuses($landlordId);
        return redirect()->back()->with('success', "Đã quét và cập nhật trạng thái mới nhất cho {$count} hợp đồng!");
    }

    /**
     * Chuyển trạng thái hợp đồng sang Hết hạn (Expired)
     */
    public function markAsExpired(Request $request, Contract $contract)
    {
        if ($contract->room->boardingHouse->user_id !== Auth::id() && $contract->tenant_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($contract->status, ['active', 'expiring'])) {
            return redirect()->back()->with('error', 'Chỉ hợp đồng đang hiệu lực hoặc sắp hết hạn mới có thể chuyển sang trạng thái Hết hạn.');
        }

        $today = now()->format('Y-m-d');
        $endDateStr = $contract->end_date ? (is_string($contract->end_date) ? substr($contract->end_date, 0, 10) : $contract->end_date->format('Y-m-d')) : null;
        $isEarlyTermination = $endDateStr && $endDateStr > $today;

        if ($isEarlyTermination) {
            $request->validate([
                'reason' => 'required|string|max:1000',
            ], [
                'reason.required' => 'Hợp đồng chưa tới ngày hết hạn. Vui lòng nhập lý do báo chấm dứt trước thời hạn.'
            ]);
        }

        $reasonText = trim($request->input('reason', ''));
        if ($isEarlyTermination) {
            $reasonText = 'Chấm dứt trước thời hạn: ' . ($reasonText ?: 'Báo kết thúc hợp đồng sớm');
        } elseif (empty($reasonText)) {
            $reasonText = 'Hợp đồng đã đến thời hạn hoặc báo kết thúc';
        }

        Contract::$allowImmutableUpdate = true;
        $contract->update([
            'status' => 'expired',
            'cancellation_reason' => $reasonText
        ]);
        Contract::$allowImmutableUpdate = false;

        $msg = $isEarlyTermination 
            ? 'Đã xác nhận chấm dứt hợp đồng sớm và chuyển sang trạng thái Hết hạn (Chờ thanh lý).'
            : 'Hợp đồng đã chuyển sang trạng thái Hết hạn (Chờ thực hiện thanh lý & quyết toán).';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Thanh lý hợp đồng (BẮT BUỘC Hợp đồng phải ở trạng thái EXPIRED)
     */
    public function liquidateContract(Request $request, Contract $contract)
    {
        if ($contract->room->boardingHouse->user_id !== Auth::id()) {
            abort(403, 'Bạn không có quyền quản lý hợp đồng này.');
        }

        // QUY TẮC BẮT BUỘC: Hợp đồng phải ở trạng thái 'expired' mới được thanh lý
        if ($contract->status !== 'expired') {
            return redirect()->back()->with('error', 'Hợp đồng phải bước vào trạng thái Hết hạn (expired) mới được phép thực hiện Thanh lý.');
        }

        $request->validate([
            'deposit_handling' => 'required|in:refund_full,refund_partial,keep_deposit',
            'deposit_refund_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $contract->update([
            'status' => 'terminated',
            'liquidated_at' => now(),
            'deposit_handling' => $request->deposit_handling,
            'deposit_refund_amount' => $request->deposit_refund_amount ?? 0,
            'cancellation_reason' => $request->notes,
        ]);

        $room = $contract->room;
        if ($room) {
            $room->update([
                'status' => 'available',
                'current_people' => max(0, $room->current_people - 1)
            ]);

            RoomPost::where('room_id', $room->id)->update(['status' => 'active']);
        }

        return redirect()->back()->with('success', 'Thanh lý hợp đồng thành công! Phòng trọ đã được trả về trạng thái Sẵn sàng cho thuê.');
    }

    /**
     * Gia hạn hợp đồng (Có kiểm tra thông tin CCCD)
     */
    public function extendContract(Request $request, Contract $contract)
    {
        if ($contract->room->boardingHouse->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'new_end_date' => 'required|date|after:' . $contract->end_date,
            'new_monthly_rent' => 'nullable|numeric|min:0',
            'tenant_cccd' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $tenant = $contract->tenant;

        // Bắt buộc kiểm tra CCCD
        $cccdNumber = $request->tenant_cccd ?: ($tenant->cccd_number ?? null);
        if (empty($cccdNumber)) {
            return redirect()->back()->with('error', 'Không thể gia hạn! Yêu cầu bổ sung thông tin CCCD/CMND của Khách thuê trước khi gia hạn hợp đồng.');
        }

        if ($tenant && $request->filled('tenant_cccd')) {
            $tenant->update(['cccd_number' => $request->tenant_cccd]);
        }

        $newRent = $request->filled('new_monthly_rent') ? $request->new_monthly_rent : $contract->monthly_rent;

        // Lưu lịch sử gia hạn
        ContractExtension::create([
            'contract_id' => $contract->id,
            'old_end_date' => $contract->end_date,
            'new_end_date' => $request->new_end_date,
            'old_monthly_rent' => $contract->monthly_rent,
            'new_monthly_rent' => $newRent,
            'tenant_cccd_number' => $cccdNumber,
            'notes' => $request->notes,
            'created_by' => Auth::id(),
        ]);

        Contract::$allowImmutableUpdate = true;
        $contract->update([
            'end_date' => $request->new_end_date,
            'monthly_rent' => $newRent,
            'status' => 'active',
        ]);
        Contract::$allowImmutableUpdate = false;

        return redirect()->back()->with('success', 'Đã gia hạn hợp đồng thành công đến ' . date('d/m/Y', strtotime($request->new_end_date)));
    }
}
