<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Contract;
use App\Models\BoardingHouse;
use App\Models\RoomResident;
use App\Models\Setting;
use App\Services\ServiceManagementService;
use App\Services\ProratedBillingService;
use App\Services\AuditLogger;
use App\Notifications\NewInvoiceNotification;
use App\Notifications\FirstMonthProratedInvoiceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class InvoiceService
{
    protected ServiceManagementService $serviceManagementService;
    protected ProratedBillingService $proratedService;

    public function __construct(
        ServiceManagementService $serviceManagementService,
        ProratedBillingService $proratedService
    ) {
        $this->serviceManagementService = $serviceManagementService;
        $this->proratedService = $proratedService;
    }

    /**
     * Lấy dữ liệu danh sách hóa đơn & hợp đồng cho trang Quản lý Hóa đơn của Chủ trọ
     */
    public function getInvoicesData(int $landlordId, ?int $boardingHouseId = null): array
    {
        $baseQuery = Invoice::whereHas('contract.room.boardingHouse', function ($q) use ($landlordId, $boardingHouseId) {
            $q->where('user_id', $landlordId);
            if ($boardingHouseId) {
                $q->where('id', $boardingHouseId);
            }
        })->with(['contract.room', 'contract.tenant', 'details.service']);

        $invoices = (clone $baseQuery)->whereNull('archived_at')->orderBy('created_at', 'desc')->take(100)->get();
        $archivedInvoices = (clone $baseQuery)->whereNotNull('archived_at')->orderBy('archived_at', 'desc')->take(100)->get();

        $activeContracts = Contract::whereHas('room.boardingHouse', function ($q) use ($landlordId, $boardingHouseId) {
            $q->where('user_id', $landlordId);
            if ($boardingHouseId) {
                $q->where('id', $boardingHouseId);
            }
        })
            ->where('status', '!=', 'terminated')
            ->with([
                'room.services',
                'tenant',
                'invoices' => function ($q) {
                    $q->orderBy('billing_month', 'desc')->with('details');
                }
            ])->get();

        $boardingHouses = BoardingHouse::where('user_id', $landlordId)
            ->where('status', 'approved')
            ->get();

        $currentMonth = date('Y-m');
        $pendingBillingContracts = Contract::whereHas('room.boardingHouse', function ($q) use ($landlordId) {
            $q->where('user_id', $landlordId);
        })
            ->whereIn('status', ['active', 'signed'])
            ->whereDoesntHave('invoices', function ($q) use ($currentMonth) {
                $q->where('billing_month', $currentMonth);
            })
            ->with([
                'room.services',
                'tenant',
                'room.boardingHouse',
                'invoices' => function ($q) {
                    $q->orderBy('billing_month', 'desc')->with('details');
                }
            ])->get();

        $services = $this->serviceManagementService->getServices($landlordId, $boardingHouseId)
            ->where('is_active', true)
            ->values();

        return [
            'invoices' => $invoices,
            'archivedInvoices' => $archivedInvoices,
            'activeContracts' => $activeContracts,
            'services' => $services,
            'boardingHouses' => $boardingHouses,
            'pendingBillingContracts' => $pendingBillingContracts,
        ];
    }

    /**
     * Tạo hóa đơn đơn lẻ
     */
    public function createInvoice(int $landlordId, array $data, $requestFiles): Invoice
    {
        return DB::transaction(function () use ($landlordId, $data, $requestFiles) {
            $contract = Contract::with('room.boardingHouse')->findOrFail($data['contract_id']);

            // 1. Kiểm tra hóa đơn đã tồn tại trong kỳ này chưa
            $exists = Invoice::where('contract_id', $contract->id)
                ->where('billing_month', $data['billing_month'])
                ->exists();
            if ($exists) {
                throw new Exception('Hóa đơn cho hợp đồng này trong kỳ này đã tồn tại!');
            }

            // 2. Lấy hóa đơn kỳ gần nhất để kế thừa ảnh & chỉ số cũ
            $lastInv = Invoice::where('contract_id', $contract->id)
                ->orderBy('billing_month', 'desc')
                ->with('details')
                ->first();

            // Cấu hình lưu 100% ảnh công tơ lên Cloudflare R2:
            $elecImgPath = isset($requestFiles['elec_meter_image'])
                ? '/storage/' . $requestFiles['elec_meter_image']->store('meter_readings', 'r2_public')
                : null;

            $elecOldImgPath = isset($requestFiles['elec_old_meter_image'])
                ? '/storage/' . $requestFiles['elec_old_meter_image']->store('meter_readings', 'r2_public')
                : null;

            $waterImgPath = isset($requestFiles['water_meter_image'])
                ? '/storage/' . $requestFiles['water_meter_image']->store('meter_readings', 'r2_public')
                : null;

            $waterOldImgPath = isset($requestFiles['water_old_meter_image'])
                ? '/storage/' . $requestFiles['water_old_meter_image']->store('meter_readings', 'r2_public')
                : null;

            $roomPrice = $contract->room ? (float) $contract->room->price : 0;
            $processedDetails = [];

            // 4. Xử lý tính toán từng hạng mục trong chi tiết hóa đơn
            foreach ($data['details'] as $d) {
                $itemName = $d['item_name'];
                $price = (float) $d['price'];

                if (!empty($d['service_id'])) {
                    $srv = \App\Models\Service::find($d['service_id']);
                    if ($srv) {
                        $pivotPrice = DB::table('room_service')
                            ->where('room_id', $contract->room_id)
                            ->where('service_id', $srv->id)
                            ->value('price');
                        $price = (!is_null($pivotPrice) && $pivotPrice !== '') ? (float) $pivotPrice : (float) $srv->price;
                    }
                }

                $quantity = (float) $d['quantity'];
                $oldIndex = isset($d['old_index']) ? (int) $d['old_index'] : null;
                $newIndex = isset($d['new_index']) ? (int) $d['new_index'] : null;
                $meterImg = null;
                $oldMeterImg = null;

                if ($itemName === 'Tiền thuê nhà') {
                    $price = $roomPrice;
                    $quantity = 1;
                } elseif (str_contains($itemName, 'Điện')) {
                    if ($lastInv) {
                        $lastElec = $lastInv->details->first(fn($dt) => str_contains($dt->item_name, 'Điện'));
                        if ($lastElec && $lastElec->new_index !== null) {
                            $oldIndex = (int) $lastElec->new_index;
                            if (!$elecOldImgPath && $lastElec->meter_image_path) {
                                $oldMeterImg = $lastElec->meter_image_path;
                            }
                        }
                    } elseif ($contract->entry_elec_index !== null) {
                        $oldIndex = (int) $contract->entry_elec_index;
                        if (!$elecOldImgPath && $contract->entry_elec_image) {
                            $oldMeterImg = $contract->entry_elec_image;
                        }
                    }
                    $quantity = $newIndex - $oldIndex;
                    $meterImg = $elecImgPath;
                    if ($elecOldImgPath)
                        $oldMeterImg = $elecOldImgPath;
                } elseif (str_contains($itemName, 'Nước')) {
                    if ($lastInv) {
                        $lastWater = $lastInv->details->first(fn($dt) => str_contains($dt->item_name, 'Nước'));
                        if ($lastWater && $lastWater->new_index !== null) {
                            $oldIndex = (int) $lastWater->new_index;
                            if (!$waterOldImgPath && $lastWater->meter_image_path) {
                                $oldMeterImg = $lastWater->meter_image_path;
                            }
                        }
                    } elseif ($contract->entry_water_index !== null) {
                        $oldIndex = (int) $contract->entry_water_index;
                        if (!$waterOldImgPath && $contract->entry_water_image) {
                            $oldMeterImg = $contract->entry_water_image;
                        }
                    }
                    $quantity = $newIndex - $oldIndex;
                    $meterImg = $waterImgPath;
                    if ($waterOldImgPath)
                        $oldMeterImg = $waterOldImgPath;
                }

                $subtotal = $price * $quantity;
                $processedDetails[] = [
                    'service_id' => $d['service_id'] ?? null,
                    'item_name' => $itemName,
                    'old_index' => $oldIndex,
                    'new_index' => $newIndex,
                    'meter_image_path' => $meterImg,
                    'old_meter_image_path' => $oldMeterImg,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            $totalAmount = collect($processedDetails)->sum('subtotal');

            // 5. Tạo bản ghi Hóa đơn
            $invoice = Invoice::create([
                'contract_id' => $contract->id,
                'invoice_code' => 'HD-' . date('Ym') . '-' . $contract->id . '-' . strtoupper(substr(uniqid(), -5)),
                'billing_month' => $data['billing_month'],
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
                'due_date' => $data['due_date'],
            ]);

            foreach ($processedDetails as $pd) {
                $pd['invoice_id'] = $invoice->id;
                InvoiceDetail::create($pd);
            }

            // 6. Gửi thông báo cho Chủ hợp đồng & Người ở ghép
            $this->sendInvoiceNotifications($contract, $invoice, $data['billing_month']);

            // 7. Ghi Log kiểm soát bất thường
            $this->logInvoiceAudit($contract, $invoice, $data['billing_month'], $totalAmount, $processedDetails);

            return $invoice;
        });
    }

    //lập hoá đơn hàng loạt cho các phòng
    public function createQuickBulkInvoices(int $landlordId, array $data, $request): array
    {
        $billingMonth = $data['billing_month'];
        $dueDate = $data['due_date'];
        // Check thời hạn nộp hoá đơn không quá 1 tháng so với hiện tại
        $maxAllowedMonth = Carbon::now()->addMonth()->format('Y-m');
        if ($billingMonth > $maxAllowedMonth) {
            throw new Exception("Kỳ thanh toán không được tạo vượt quá 1 tháng so với hiện tại (Kỳ tối đa được phép: Tháng {$maxAllowedMonth})!");
        }
        $createdCount = 0;
        DB::transaction(function () use ($landlordId, $data, $request, $billingMonth, $dueDate, &$createdCount) {
            foreach ($data['readings'] as $index => $r) {
                $contractId = $r['contract_id'];
                $contract = Contract::with(['room.services', 'tenant'])->findOrFail($contractId);
                // Kiểm tra phòng có thuộc sở hữu của chủ trọ đang đăng nhập không
                if ($contract->room?->boardingHouse?->user_id !== $landlordId) {
                    continue;
                }
                // Bỏ qua nếu hoá đơn cho kỳ này đã được tạo trước đó
                $exists = Invoice::where('contract_id', $contractId)
                    ->where('billing_month', $billingMonth)
                    ->exists();
                if ($exists) {
                    continue;
                }
                // Lấy hoá đơn kỳ trước để tính chỉ số cũ
                $lastInv = Invoice::where('contract_id', $contractId)
                    ->orderBy('billing_month', 'desc')
                    ->with('details')
                    ->first();
                // Upload ảnh chỉ số điện nước
                $elecImgPath = null;
                $elecOldImgPath = null;
                $elecFileKey = "readings.{$index}.elec_image";
                if ($request->hasFile($elecFileKey)) {
                    $elecImgPath = '/storage/' . $request->file($elecFileKey)->store('meter_readings', 'r2_public');
                }
                $waterImgPath = null;
                $waterOldImgPath = null;
                $waterFileKey = "readings.{$index}.water_image";
                if ($request->hasFile($waterFileKey)) {
                    $waterImgPath = '/storage/' . $request->file($waterFileKey)->store('meter_readings', 'r2_public');
                }
                // Đơn giá mặc định
                $roomPrice = $contract->room ? (float) $contract->room->price : 0;
                $elecPrice = 3000;
                $waterPrice = 15000;
                // Ưu tiên giá dịch vụ đã đóng băng trong room_service pivot
                if ($contract->room) {
                    foreach ($contract->room->services as $service) {
                        $pivotPrice = DB::table('room_service')
                            ->where('room_id', $contract->room_id)
                            ->where('service_id', $service->id)
                            ->value('price');
                        $effectivePrice = (!is_null($pivotPrice) && $pivotPrice !== '') ? (float) $pivotPrice : (float) $service->price;
                        if ($service->type === 'per_kwh' || str_contains(mb_strtolower($service->name), 'điện')) {
                            $elecPrice = $effectivePrice;
                        }
                        if ($service->type === 'per_m3' || str_contains(mb_strtolower($service->name), 'nước')) {
                            $waterPrice = $effectivePrice;
                        }
                    }
                }
                // Tính chỉ số cũ điện
                $elecOld = 0;
                if ($lastInv) {
                    $lastElec = $lastInv->details->first(fn($dt) => str_contains(strtolower($dt->item_name), 'điện'));
                    if ($lastElec && $lastElec->new_index !== null) {
                        $elecOld = (int) $lastElec->new_index;
                        if ($lastElec->meter_image_path) {
                            $elecOldImgPath = $lastElec->meter_image_path;
                        }
                    }
                } else if ($contract->entry_elec_index !== null) {
                    $elecOld = (int) $contract->entry_elec_index;
                }
                // Tính chỉ số cũ nước 
                $waterOld = 0;
                if ($lastInv) {
                    $lastWater = $lastInv->details->first(fn($dt) => str_contains(strtolower($dt->item_name), 'nước'));
                    if ($lastWater && $lastWater->new_index !== null) {
                        $waterOld = (int) $lastWater->new_index;
                        if ($lastWater->meter_image_path) {
                            $waterOldImgPath = $lastWater->meter_image_path;
                        }
                    }
                } else if ($contract->entry_water_index !== null) {
                    $waterOld = (int) $contract->entry_water_index;
                }
                if ((int) $r['elec_new'] < $elecOld) {
                    throw new Exception("Chỉ số điện mới của phòng " . ($contract->room ? $contract->room->room_number : $contractId) . " không được nhỏ hơn chỉ số cũ ({$elecOld})!");
                }
                if ((int) $r['water_new'] < $waterOld) {
                    throw new Exception("Chỉ số nước mới của phòng " . ($contract->room ? $contract->room->room_number : $contractId) . " không được nhỏ hơn chỉ số cũ ({$waterOld})!");
                }
                $elecQty = (int) $r['elec_new'] - $elecOld;
                $waterQty = (int) $r['water_new'] - $waterOld;
                $processedDetails = [];
                // 1. Tiền thuê nhà
                $processedDetails[] = [
                    'service_id' => null,
                    'item_name' => 'Tiền thuê nhà',
                    'old_index' => null,
                    'new_index' => null,
                    'meter_image_path' => null,
                    'old_meter_image_path' => null,
                    'quantity' => 1,
                    'price' => $roomPrice,
                    'subtotal' => $roomPrice,
                ];
                // 2. Tiền điện
                $processedDetails[] = [
                    'service_id' => null,
                    'item_name' => 'Tiền Điện',
                    'old_index' => $elecOld,
                    'new_index' => (int) $r['elec_new'],
                    'meter_image_path' => $elecImgPath,
                    'old_meter_image_path' => $elecOldImgPath,
                    'quantity' => $elecQty,
                    'price' => $elecPrice,
                    'subtotal' => $elecPrice * $elecQty,
                ];
                // 3. Tiền nước
                $processedDetails[] = [
                    'service_id' => null,
                    'item_name' => 'Tiền Nước',
                    'old_index' => $waterOld,
                    'new_index' => (int) $r['water_new'],
                    'meter_image_path' => $waterImgPath,
                    'old_meter_image_path' => $waterOldImgPath,
                    'quantity' => $waterQty,
                    'price' => $waterPrice,
                    'subtotal' => $waterPrice * $waterQty,
                ];
                // 4. Dịch vụ cố định / theo người của phòng
                if ($contract->room) {
                    foreach ($contract->room->services as $service) {
                        $pivotPrice = DB::table('room_service')
                            ->where('room_id', $contract->room_id)
                            ->where('service_id', $service->id)
                            ->value('price');
                        $effectivePrice = (!is_null($pivotPrice) && $pivotPrice !== '') ? (float) $pivotPrice : (float) $service->price;
                        if ($service->type === 'fixed') {
                            $processedDetails[] = [
                                'service_id' => $service->id,
                                'item_name' => $service->name,
                                'old_index' => null,
                                'new_index' => null,
                                'meter_image_path' => null,
                                'old_meter_image_path' => null,
                                'quantity' => 1,
                                'price' => $effectivePrice,
                                'subtotal' => $effectivePrice,
                            ];
                        } elseif ($service->type === 'per_person') {
                            $ppl = $contract->room->current_people ?: 1;
                            $processedDetails[] = [
                                'service_id' => $service->id,
                                'item_name' => $service->name,
                                'old_index' => null,
                                'new_index' => null,
                                'meter_image_path' => null,
                                'old_meter_image_path' => null,
                                'quantity' => $ppl,
                                'price' => $effectivePrice,
                                'subtotal' => $effectivePrice * $ppl,
                            ];
                        }
                    }
                }
                $totalAmount = collect($processedDetails)->sum('subtotal');
                // Tạo hoá đơn
                $invoice = Invoice::create([
                    'contract_id' => $contractId,
                    'invoice_code' => 'HD-' . date('Ym') . '-' . $contractId . '-' . strtoupper(substr(uniqid(), -5)),
                    'billing_month' => $billingMonth,
                    'total_amount' => $totalAmount,
                    'status' => 'unpaid',
                    'due_date' => $dueDate,
                ]);
                foreach ($processedDetails as $pd) {
                    $pd['invoice_id'] = $invoice->id;
                    InvoiceDetail::create($pd);
                }
                // Gửi thông báo cho khách thuê
                $this->sendInvoiceNotifications($contract, $invoice, $billingMonth);
                //ghi log
                $this->logInvoiceAudit($contract, $invoice, $billingMonth, $totalAmount, $processedDetails);
                $createdCount++;
            }
        });
        return ['created_count' => $createdCount];
    }

    /**
     * Cập nhật thông tin Hóa đơn chưa thanh toán
     */
    public function updateInvoice(int $id, array $data, int $landlordId): Invoice
    {

        $invoice = Invoice::with('contract.room.boardingHouse')->findOrFail($id);
        //check quyền sở hữu
        $this->checkInvoiceOwnership($invoice, $landlordId);
        if ($invoice->status === 'paid') {
            throw new Exception('Hóa đơn đã hoàn thành thanh toán không thể chỉnh sửa!');
        }

        return DB::transaction(function () use ($invoice, $data) {
            //xoá toàn bộ chi tiết cũ để tránh dữ liệu nhân đôi
            $invoice->details()->delete();
            $totalAmount = 0;
            foreach ($data['details'] as $d) {
                // Validate chỉ số công tơ: mới không được nhỏ hơn cũ
                $oldIdx = isset($d['old_index']) ? (int) $d['old_index'] : null;
                $newIdx = isset($d['new_index']) ? (int) $d['new_index'] : null;
                if ($oldIdx !== null && $newIdx !== null && $newIdx < $oldIdx) {
                    throw new Exception("Chỉ số mới của '{$d['item_name']}' ({$newIdx}) không được nhỏ hơn chỉ số cũ ({$oldIdx})!");
                }
                $subtotal = (float) $d['price'] * (float) $d['quantity'];
                $totalAmount += $subtotal;
                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $d['service_id'] ?? null,
                    'item_name' => $d['item_name'] ?? 'Dịch vụ',
                    'old_index' => $d['old_index'] ?? null,
                    'new_index' => $d['new_index'] ?? null,
                    'quantity' => $d['quantity'],
                    'price' => $d['price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $paidAmount = (float) ($invoice->paid_amount ?? 0);
            $newStatus = $invoice->status;
            $newPaidAt = $invoice->paid_at;

            if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                $newStatus = 'paid';
                $paidAmount = $totalAmount;
                $newPaidAt = $invoice->paid_at ?? now();
            } elseif ($paidAmount > 0) {
                $newStatus = 'partially_paid';
            } else {
                $newStatus = 'unpaid';
            }
            $invoice->update([
                'total_amount' => $totalAmount,
                'due_date' => $data['due_date'],
                'status' => $newStatus,
                'paid_amount' => $paidAmount,
                'paid_at' => $newPaidAt,
            ]);
            return $invoice;
        });
    }

    /**
     * Cập nhật trạng thái thanh toán (hỗ trợ thanh toán 1 phần & đủ)
     */
    public function updateInvoiceStatus(int $id, string $status, ?float $paidAmount, int $landlordId): Invoice
    {
        $invoice = Invoice::with(['contract.room.boardingHouse', 'contract.tenant'])->findOrFail($id);
        //check quyền sở hữu
        $this->checkInvoiceOwnership($invoice, $landlordId);
        $additionalPaid = (float) ($paidAmount ?? 0);
        $newTotalPaid = ($invoice->paid_amount ?? 0) + $additionalPaid;

        if ($status === 'paid' || $newTotalPaid >= $invoice->total_amount) {
            $invoice->update([
                'status' => 'paid',
                'paid_amount' => $invoice->total_amount,
                'paid_at' => now(),
            ]);
        } elseif ($newTotalPaid > 0) {
            $invoice->update([
                'status' => 'partially_paid',
                'paid_amount' => $newTotalPaid,
            ]);
        } else {
            $invoice->update([
                'status' => 'unpaid',
                'paid_amount' => 0,
                'paid_at' => null,
            ]);
        }

        // Gửi thông báo cho Khách thuê khi xác nhận thanh toán thành công
        if (in_array($invoice->status, ['paid', 'partially_paid'])) {
            try {
                $tenant = $invoice->contract->tenant ?? null;
                if ($tenant) {
                    $tenant->notify(new \App\Notifications\InvoicePaymentConfirmedNotification($invoice));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Gửi thông báo xác nhận thanh toán hóa đơn thất bại: " . $e->getMessage());
            }
        }

        return $invoice;
    }

    /**
     * Lưu trữ hóa đơn
     */
    public function archiveInvoice(int $id, int $landlordId): bool
    {
        $invoice = Invoice::with('contract.room.boardingHouse')->findOrFail($id);
        $this->checkInvoiceOwnership($invoice, $landlordId);
        return $invoice->update(['archived_at' => now()]);
    }

    /**
     * Khôi phục hóa đơn từ lưu trữ
     */
    public function restoreInvoice(int $id, int $landlordId): bool
    {
        $invoice = Invoice::with('contract.room.boardingHouse')->findOrFail($id);
        $this->checkInvoiceOwnership($invoice, $landlordId);
        return $invoice->update(['archived_at' => null]);
    }

    /**
     * Xóa hóa đơn chưa thanh toán
     */
    public function deleteInvoice(int $id, int $landlordId): bool
    {
        $invoice = Invoice::with(['contract.room.boardingHouse', 'details'])->findOrFail($id);
        $this->checkInvoiceOwnership($invoice, $landlordId);
        if ($invoice->status === 'paid') {
            throw new Exception('Không thể xóa hóa đơn đã hoàn thành thanh toán! Vui lòng lưu trữ.');
        }
        foreach ($invoice->details as $detail) {
            foreach (['meter_image_path', 'old_meter_image_path'] as $field) {
                if (!empty($detail->$field)) {
                    // Bỏ prefix '/storage/' để lấy path thực trên R2
                    $r2Path = ltrim($detail->$field, '/storage/');
                    Storage::disk('r2_public')->delete($r2Path);
                }
            }
        }
        $invoice->details()->delete();
        return $invoice->delete();
    }

    // ========== PRIVATE HELPER METHODS ==========

    private function sendInvoiceNotifications(Contract $contract, Invoice $invoice, string $billingMonth): void
    {
        $recipients = collect();
        if ($contract->tenant) {
            $recipients->push($contract->tenant);
        }
        $roomResidents = RoomResident::where('room_id', $contract->room_id)
            ->where('status', 'active')
            ->with('user')
            ->get();
        foreach ($roomResidents as $res) {
            if ($res->user && $res->user->id !== $contract->tenant_id) {
                $recipients->push($res->user);
            }
        }

        if ($recipients->isNotEmpty()) {
            $isFirstInvoice = !Invoice::where('contract_id', $contract->id)->where('id', '!=', $invoice->id)->exists();
            foreach ($recipients as $recipient) {
                if ($isFirstInvoice) {
                    $proratedInfo = $this->proratedService->calculateProratedRent($contract, $billingMonth);
                    if ($proratedInfo['should_prorate'] || $proratedInfo['is_grace_period']) {
                        $recipient->notify(new FirstMonthProratedInvoiceNotification($invoice, (int) $proratedInfo['days_occupied'], $proratedInfo['reason']));
                    } else {
                        $recipient->notify(new NewInvoiceNotification($invoice));
                    }
                } else {
                    $recipient->notify(new NewInvoiceNotification($invoice));
                }
            }
        }
    }

    private function logInvoiceAudit(Contract $contract, Invoice $invoice, string $billingMonth, float $totalAmount, array $processedDetails): void
    {
        $isAbnormal = false;
        $reasons = [];
        $maxInvoiceAmount = (float) (Setting::where('key', 'warning_invoice_amount')->value('value') ?? 10000000);
        if ($totalAmount > $maxInvoiceAmount) {
            $isAbnormal = true;
            $reasons[] = "Tổng hoá đơn lớn hơn ngưỡng thiết lập (" . number_format($totalAmount) . " đ/Ngưỡng: " . number_format($maxInvoiceAmount) . "đ)";
        }
        foreach ($processedDetails as $pd) {
            if (str_contains($pd['item_name'], 'Điện') && $pd['quantity'] > 1000) {
                $isAbnormal = true;
                $reasons[] = "Tiêu thụ điện bất thường: " . $pd['quantity'] . "kWh";
            }
            if (str_contains($pd['item_name'], 'Nước') && $pd['quantity'] > 50) {
                $isAbnormal = true;
                $reasons[] = "Tiêu thụ Nước bất thường: " . $pd['quantity'] . "m3";
            }
        }

        $action = $isAbnormal ? 'abnormal_invoice' : 'bulk_invoice';
        $logMesseage = "Chủ trọ " . auth()->user()->name . " tạo hoá đơn {$invoice->invoice_code} cho phòng " . ($contract->room->room_number ?? 'N/A') . " (Kỳ: {$billingMonth})";
        if ($isAbnormal) {
            $logMesseage .= " [CẢNH BÁO BẤT THƯỜNG]: " . implode(';', $reasons);
        } else {
            $logMesseage .= " với tổng tiền " . number_format($totalAmount) . "đ";
        }
        AuditLogger::log($action, $logMesseage, $isAbnormal);
    }
    //check xem hoá đơn này có thuộc về chủ trọ đang đăng nhập hay không
    private function checkInvoiceOwnership(Invoice $invoice, int $landlordId): void
    {
        $ownerId = $invoice->contract?->room?->boardingHouse?->user_id;
        if ($ownerId !== $landlordId) {
            throw new Exception('Bạn không có quyền thao tác trên hoá đơn này!');
        }
    }
}
