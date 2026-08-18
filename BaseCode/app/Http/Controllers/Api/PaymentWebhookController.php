<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Tự động tiếp nhận Webhook ngân hàng (SePay, PayOS hoặc Bank API)
     */
    public function handleWebhook(Request $request)
    {
        $content = $request->input('content') 
            ?? $request->input('description') 
            ?? $request->input('data.description') 
            ?? $request->input('memo', '');
            
        $amount = floatval($request->input('transferAmount') 
            ?? $request->input('amount') 
            ?? $request->input('data.amount', 0));

        if (empty($content)) {
            return response()->json(['success' => false, 'message' => 'Nội dung chuyển khoản trống'], 400);
        }

        Log::info("Webhook Payment Received:", ['content' => $content, 'amount' => $amount]);

        $invoice = $this->findInvoiceFromContent($content);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy hóa đơn khớp với nội dung: ' . $content
            ], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Hóa đơn này đã được thanh toán trước đó.',
                'invoice_code' => $invoice->invoice_code
            ]);
        }

        // Cập nhật trạng thái sang Đã thanh toán
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thanh toán tự động thành công cho hóa đơn #' . $invoice->invoice_code,
            'invoice_id' => $invoice->id,
            'invoice_code' => $invoice->invoice_code,
            'total_amount' => $invoice->total_amount,
            'paid_at' => $invoice->paid_at->toDateTimeString()
        ]);
    }

    /**
     * Endpoint kiểm tra trạng thái nhanh cho Frontend Polling
     */
    public function checkStatus($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn'], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $invoice->status,
            'paid_at' => $invoice->paid_at ? $invoice->paid_at->toDateTimeString() : null
        ]);
    }

    /**
     * Endpoint Giả lập thanh toán cho nút Test trên giao diện Local
     */
    public function simulatePayment(Request $request)
    {
        $invoiceId = $request->input('invoice_id');
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Hóa đơn không tồn tại'], 404);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Giả lập thanh toán ngân hàng thành công!',
            'invoice' => $invoice
        ]);
    }

    /**
     * Tìm hóa đơn theo mã HĐ hoặc theo Mẫu 1 (P101... TT thang 202609 / 2026-09)
     */
    private function findInvoiceFromContent($content)
    {
        Log::info("Parsing webhook content: " . $content);

        // 1. Chuẩn hóa chuỗi content (Bỏ dấu, ký tự đặc biệt, chuyển in hoa)
        $cleanContent = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $content));

        // 2. Tìm tất cả hóa đơn chưa thanh toán
        $unpaidInvoices = Invoice::where('status', 'unpaid')->with(['contract.room', 'contract.tenant'])->get();

        // 2a. Thử so sánh mã hóa đơn trực tiếp (Ví dụ HD202608464 hay 202608464)
        foreach ($unpaidInvoices as $inv) {
            $cleanCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $inv->invoice_code));
            if (!empty($cleanCode) && str_contains($cleanContent, $cleanCode)) {
                return $inv;
            }

            // Lấy riêng chuỗi số của mã hóa đơn (VD: HD-202608-464 -> 202608464)
            $codeNumbersOnly = preg_replace('/[^0-9]/', '', $inv->invoice_code);
            if (!empty($codeNumbersOnly) && strlen($codeNumbersOnly) >= 4 && str_contains($cleanContent, $codeNumbersOnly)) {
                return $inv;
            }
        }

        // 3. Phân tích nội dung chuyển khoản theo cấu trúc: P[Số phòng] ... TT thang [Tháng]
        preg_match('/P(\w+)/i', $content, $roomMatches);
        $roomNum = $roomMatches[1] ?? null;

        $billingMonthFound = null;
        if (preg_match('/([\d]{4})[-\/]?([\d]{2})/', $content, $m)) {
            // Định dạng YYYY-MM hoặc YYYYMM (ví dụ 202609 -> 2026-09)
            $billingMonthFound = $m[1] . '-' . sprintf('%02d', $m[2]);
        } elseif (preg_match('/([\d]{2})[-\/]([\d]{4})/', $content, $m)) {
            // Định dạng MM-YYYY hoặc MM/YYYY (ví dụ 09/2026 -> 2026-09)
            $billingMonthFound = $m[2] . '-' . sprintf('%02d', $m[1]);
        }

        foreach ($unpaidInvoices as $inv) {
            $invMonth = str_replace('/', '-', $inv->billing_month);
            $invRoomName = $inv->contract->room->name ?? '';
            $cleanRoomName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $invRoomName));

            if ($billingMonthFound && ($invMonth === $billingMonthFound || str_contains($invMonth, $billingMonthFound))) {
                if ($roomNum && (str_contains($cleanRoomName, strtoupper($roomNum)) || str_contains($cleanContent, $cleanRoomName))) {
                    return $inv;
                }
            }
        }

        // 4. Tìm theo Tên khách thuê trong chuỗi chuyển khoản
        foreach ($unpaidInvoices as $inv) {
            $tenantName = $inv->contract->tenant->name ?? '';
            $cleanTenantName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $tenantName));
            if (!empty($cleanTenantName) && strlen($cleanTenantName) > 4 && str_contains($cleanContent, $cleanTenantName)) {
                return $inv;
            }
        }

        return null;
    }
}
