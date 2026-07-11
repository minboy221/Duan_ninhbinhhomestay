<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_invoice_creation_and_payment_notification_flow()
    {
        $landlord = User::where('role', 'landlord')->first();
        $tenant = User::where('role', 'tenant')->first();
        $contract = Contract::where('status', 'signed')->first();

        if (!$landlord || !$tenant || !$contract) {
            $this->markTestSkipped('Landlord, tenant, or contract not seeded.');
        }

        // 1. Create Invoice as Landlord
        $response = $this->actingAs($landlord)->post(route('landlord.invoices.store'), [
            'contract_id' => $contract->id,
            'billing_month' => '2026-08',
            'due_date' => '2026-08-15',
            'details' => [
                [
                    'item_name' => 'Tiền thuê nhà',
                    'price' => 2500000,
                    'quantity' => 1,
                    'subtotal' => 2500000,
                ],
                [
                    'item_name' => 'Tiền điện',
                    'price' => 3500,
                    'quantity' => 100,
                    'subtotal' => 350000,
                    'old_index' => 1200,
                    'new_index' => 1300,
                ],
            ]
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302); // Redirect back on success
        
        $invoice = Invoice::where('contract_id', $contract->id)
            ->where('billing_month', '2026-08')
            ->first();

        $this->assertNotNull($invoice);
        $this->assertEquals(2850000, $invoice->total_amount);

        // 2. Tenant reports payment
        $response = $this->actingAs($tenant)->post(route('invoices.notify-payment', $invoice->id), [
            'payment_method' => 'qr'
        ]);

        $response->assertStatus(302);
    }
}
