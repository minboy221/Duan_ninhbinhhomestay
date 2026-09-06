<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\BoardingHouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PaymentWebhookTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test 1: Kiểm tra Webhook báo lỗi 400 khi nội dung chuyển khoản bị rỗng
     */
    public function test_webhook_fails_when_content_is_empty()
    {
        $response = $this->postJson('/api/webhooks/payment', [
            'content' => '',
            'amount' => 500000
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Nội dung chuyển khoản trống'
                 ]);
    }

    /**
     * Test 2: Kiểm tra Webhook báo lỗi 404 khi mã hóa đơn không tồn tại trong hệ thống
     */
    public function test_webhook_returns_404_when_invoice_not_found()
    {
        $response = $this->postJson('/api/webhooks/payment', [
            'content' => 'Thanh toan HD9999999999',
            'amount' => 1000000
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                 ]);
    }

    /**
     * Test 3: Kiểm tra Webhook tự động gạch nợ thành công cho hóa đơn tiền trọ
     */
    public function test_webhook_successfully_processes_invoice_payment()
    {
        // 1. Tạo giả lập dữ liệu hóa đơn cần thanh toán
        $landlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'user']);
        
        $house = BoardingHouse::create([
            'user_id' => $landlord->id,
            'name' => 'Nhà Trọ Test Webhook',
            'address_detail' => '123 Đường Test',
            'province' => 'Ninh Bình',
            'district' => 'TP Ninh Bình',
            'status' => 'approved'
        ]);

        $room = Room::create([
            'boarding_house_id' => $house->id,
            'room_number' => 'P101',
            'price' => 2000000,
            'area' => 25,
            'capacity' => 2,
            'status' => 'rented'
        ]);

        $contract = \App\Models\Contract::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'deposit_amount' => 2000000,
            'monthly_rent' => 2000000,
            'status' => 'active',
        ]);

        $invoiceCode = 'HDTEST' . rand(1000, 9999);
        $invoice = Invoice::create([
            'contract_id' => $contract->id,
            'invoice_code' => $invoiceCode,
            'billing_month' => now()->format('Y-m'),
            'total_amount' => 2000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5)
        ]);

        // 2. Gửi Webhook giả lập từ ngân hàng SePay/PayOS
        $response = $this->postJson('/api/webhooks/payment', [
            'content' => "Thanh toan tien tro " . $invoiceCode,
            'transferAmount' => 2000000
        ]);

        // 3. Phán đoán (Assert) kết quả
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'invoice_code' => $invoiceCode
                 ]);

        // 4. Kiểm tra dữ liệu DB đã được tự động chuyển thành 'paid'
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
            'paid_amount' => 2000000
        ]);
    }
}
