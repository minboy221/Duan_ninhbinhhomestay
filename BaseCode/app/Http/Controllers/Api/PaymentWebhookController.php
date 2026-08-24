<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandlordSubscription;
use App\Services\SubscriptionService;
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

        // 1. Kiểm tra nếu là thanh toán mua gói dịch vụ chủ trọ (chứa mã SUB)
        if (preg_match('/SUB\d+/', $content, $matches)) {
            $subCode = $matches[0];
            $subscription = LandlordSubscription::where('payment_code', $subCode)
                ->where('status', 'pending')
                ->first();
            if ($subscription) {
                // Kích hoạt gói dịch vụ
                $subscriptionService = app(SubscriptionService::class);
                $subscriptionService->activateSubscription($subscription);
                Log::info("Tự động kích hoạt gói thành công qua Webhook:", [
                    'payment_code' => $subCode,
                    'user_id' => $subscription->user_id,
                    'plan_id' => $subscription->plan_id
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán & Tự động kích hoạt gói dịch vụ thành công cho mã ' . $subCode,
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'plan_name' => $subscription->plan->name ?? '',
                    'start_date' => $subscription->start_date ? $subscription->start_date->toDateString() : null,
                    'end_date' => $subscription->end_date ? $subscription->end_date->toDateString() : null,
                ]);
            }
        }

        // 2. Nếu không phải mua gói -> kiểm tra hoá đơn tiền trọ
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

        // Cập nhật số tiền đã thanh toán tích lũy
        $newPaidAmount = floatval($invoice->paid_amount) + $amount;
        $totalAmount = floatval($invoice->total_amount);

        if ($newPaidAmount >= $totalAmount - 1) { // Trừ hao tròn tiền 1đ
            $invoice->update([
                'status' => 'paid',
                'paid_amount' => $totalAmount,
                'paid_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thanh toán hoàn tất 100% cho hóa đơn #' . $invoice->invoice_code,
                'invoice_id' => $invoice->id,
                'invoice_code' => $invoice->invoice_code,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'paid_at' => $invoice->paid_at->toDateTimeString()
            ]);
        } else {
            $invoice->update([
                'status' => 'partially_paid',
                'paid_amount' => $newPaidAmount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ghi nhận thanh toán 1 phần cho hóa đơn #' . $invoice->invoice_code . ' (Đã nhận ' . number_format($newPaidAmount) . ' / ' . number_format($totalAmount) . ' đ)',
                'invoice_id' => $invoice->id,
                'invoice_code' => $invoice->invoice_code,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'status' => 'partially_paid'
            ]);
        }
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
        $amount = floatval($request->input('amount', 0));
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Hóa đơn không tồn tại'], 404);
        }

        $totalAmount = floatval($invoice->total_amount);

        if ($amount > 0) {
            $newPaidAmount = floatval($invoice->paid_amount) + $amount;
            if ($newPaidAmount >= $totalAmount - 1) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_amount' => $totalAmount,
                    'paid_at' => now()
                ]);
            } else {
                $invoice->update([
                    'status' => 'partially_paid',
                    'paid_amount' => $newPaidAmount
                ]);
            }
        } else {
            $invoice->update([
                'status' => 'paid',
                'paid_amount' => $totalAmount,
                'paid_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Giả lập thanh toán ngân hàng thành công!',
            'status' => $invoice->status,
            'paid_amount' => $invoice->paid_amount,
            'invoice' => $invoice
        ]);
    }

    /**
     * Tìm hóa đơn từ nội dung chuyển khoản ngân hàng
     */
    private function findInvoiceFromContent($content)
    {
        Log::info("Parsing webhook content: " . $content);

        // 1. Chuẩn hóa chuỗi content (Bỏ dấu, ký tự đặc biệt, chuyển in hoa)
        $cleanContent = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $content));

        // 2. Tìm theo Mã hóa đơn trực tiếp (VD: HD202608464 hoặc HD-202608-464)
        if (preg_match('/(HD|INV)[-\w\d]+/i', $content, $matches)) {
            $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $matches[0]));
            $inv = Invoice::whereIn('status', ['unpaid', 'partially_paid'])
                ->get()
                ->first(function ($i) use ($code) {
                    $cleanInvCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $i->invoice_code));
                    return !empty($cleanInvCode) && (str_contains($cleanInvCode, $code) || str_contains($code, $cleanInvCode));
                });
            if ($inv) return $inv;
        }

        // 3. Quét danh sách hóa đơn chưa thanh toán hoặc thanh toán 1 phần
        $unpaidInvoices = Invoice::whereIn('status', ['unpaid', 'partially_paid'])
            ->with(['contract.room', 'contract.tenant'])
            ->get();

        // 3a. So sánh mã hóa đơn hoặc chuỗi số hóa đơn
        foreach ($unpaidInvoices as $inv) {
            $cleanCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $inv->invoice_code));
            if (!empty($cleanCode) && str_contains($cleanContent, $cleanCode)) {
                return $inv;
            }

            $codeNumbersOnly = preg_replace('/[^0-9]/', '', $inv->invoice_code);
            if (!empty($codeNumbersOnly) && strlen($codeNumbersOnly) >= 5 && str_contains($cleanContent, $codeNumbersOnly)) {
                return $inv;
            }
        }

        // 3b. Phân tích cấu trúc Phòng + Tháng thanh toán (Ví dụ: P101 ... TT thang 06-2026)
        preg_match('/P(\w+)/i', $content, $roomMatches);
        $roomNum = $roomMatches[1] ?? null;

        $billingMonthFound = null;
        if (preg_match('/([\d]{4})[-\/]?([\d]{2})/', $content, $m)) {
            $billingMonthFound = $m[1] . '-' . sprintf('%02d', $m[2]);
        } elseif (preg_match('/([\d]{2})[-\/]([\d]{4})/', $content, $m)) {
            $billingMonthFound = $m[2] . '-' . sprintf('%02d', $m[1]);
        }

        if ($roomNum || $billingMonthFound) {
            foreach ($unpaidInvoices as $inv) {
                $invMonth = str_replace('/', '-', $inv->billing_month);
                $invRoomName = $inv->contract->room->room_number ?? $inv->contract->room->name ?? '';
                $cleanRoomName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $invRoomName));

                $monthMatches = !$billingMonthFound || $invMonth === $billingMonthFound || str_contains($invMonth, $billingMonthFound);
                $roomMatches = !$roomNum || ($cleanRoomName && str_contains($cleanRoomName, strtoupper($roomNum)));

                if ($monthMatches && $roomMatches && ($roomNum || $billingMonthFound)) {
                    return $inv;
                }
            }
        }

        // 3c. Tìm theo Tên khách thuê trong nội dung chuyển khoản
        foreach ($unpaidInvoices as $inv) {
            $tenantName = $inv->contract->tenant->name ?? '';
            $cleanTenantName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $tenantName));
            if (!empty($cleanTenantName) && strlen($cleanTenantName) >= 5 && str_contains($cleanContent, $cleanTenantName)) {
                return $inv;
            }
        }

        return null;
    }

    /**
     * Endpoint Giả lập thanh toán mua gói dịch vụ cho nút Test Local
     */
    public function simulateSubscriptionPayment(Request $request)
    {
        $subscriptionId = $request->input('subscription_id');
        $subscription = LandlordSubscription::find($subscriptionId);
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn đăng ký gói không tồn tại'
            ], 404);
        }
        $subscriptionService = app(SubscriptionService::class);
        $subscriptionService->activateSubscription($subscription);
        return response()->json([
            'success' => true,
            'message' => 'Giả lập thanh toán ngân hàng cho gói dịch vụ thành công!',
            'subscription' => $subscription->load('plan')
        ]);
    }
}
