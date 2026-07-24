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
     * Tìm hóa đơn theo mã HĐ hoặc theo Mẫu 1 (P101... 06-2026)
     */
    private function findInvoiceFromContent($content)
    {
        // 1. Tìm theo Mã hóa đơn trực tiếp (HD...)
        if (preg_match('/HD[-\w\d]+/', $content, $matches)) {
            $code = $matches[0];
            $inv = Invoice::where('invoice_code', $code)->orWhere('invoice_code', '#' . $code)->first();
            if ($inv) return $inv;
        }

        // 2. Tìm theo Mẫu 1: P[Số phòng] ... TT thang [Tháng]
        // Ví dụ: P101 Tran Thi Nguoi Thue TT thang 06-2026
        if (preg_match('/P(\w+)\s+.*?\s*TT\s+thang\s+([\d]{2}[-\/][\d]{4})/i', $content, $matches)) {
            $roomNum = $matches[1];
            $rawMonth = str_replace('-', '/', $matches[2]);

            $parts = explode('/', $rawMonth);
            $billingMonth = count($parts) === 2 ? $parts[1] . '-' . sprintf('%02d', $parts[0]) : $rawMonth;

            $inv = Invoice::whereHas('contract.room', function ($q) use ($roomNum) {
                $q->where('room_number', 'LIKE', '%' . $roomNum . '%');
            })
            ->where(function ($q) use ($rawMonth, $billingMonth) {
                $q->where('billing_month', $rawMonth)->orWhere('billing_month', $billingMonth);
            })
            ->where('status', 'unpaid')
            ->first();

            if ($inv) return $inv;
        }

        // 3. Fallback: Tìm bất kỳ hóa đơn nào chưa thanh toán mà invoice_code xuất hiện trong content
        $unpaidInvoices = Invoice::where('status', 'unpaid')->get();
        foreach ($unpaidInvoices as $inv) {
            $cleanCode = str_replace(['#', '-'], '', $inv->invoice_code);
            $cleanContent = str_replace(['#', '-'], '', $content);
            if (stripos($cleanContent, $cleanCode) !== false) {
                return $inv;
            }
        }

        return null;
    }
}
