<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_can_create_and_update_a_case_invoice(): void
    {
        $user = $this->createUser();
        $client = $this->createClient();
        $case = $this->createCase($client);

        $this->actingAs($user)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'case_id' => $case->id,
            'issue_date' => '2026-11-27',
            'due_date' => '2026-12-27',
            'status' => 'draft',
            'currency' => 'usd',
            'notes' => 'Case fees',
            'items' => [
                ['description' => 'Consultation', 'quantity' => '2', 'unit_price' => '100', 'tax_percent' => '10'],
                ['description' => 'Filing fee', 'quantity' => '1', 'unit_price' => '50', 'tax_percent' => '0'],
            ],
        ])->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('USD', $invoice->currency);
        $this->assertSame(270.0, (float) $invoice->total);
        $this->assertSame(20.0, (float) $invoice->tax_total);
        $this->assertSame($client->id, $invoice->client_id);
        $this->assertSame($case->id, $invoice->case_id);
        $this->assertCount(2, $invoice->items);

        $this->actingAs($user)->put(route('invoices.update', $invoice), [
            'client_id' => $client->id,
            'case_id' => '',
            'issue_date' => '2026-11-27',
            'due_date' => '',
            'status' => 'sent',
            'currency' => 'bdt',
            'items' => [
                ['description' => 'Court appearance', 'quantity' => '1', 'unit_price' => '300', 'tax_percent' => '5'],
            ],
        ])->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame(315.0, (float) $invoice->total);
        $this->assertSame('BDT', $invoice->currency);
        $this->assertNull($invoice->case_id);
    }

    public function test_payment_updates_invoice_status_and_cannot_exceed_balance(): void
    {
        $user = $this->createUser();
        $client = $this->createClient();
        $invoice = $this->createInvoice($client, $user);

        $this->actingAs($user)->post(route('invoices.payments.store', $invoice), [
            'amount' => '40',
            'paid_at' => '2026-11-27 10:00:00',
            'method' => 'bank',
            'reference' => 'TXN-100',
        ])->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertSame(40.0, $invoice->paidAmount());
        $this->assertSame(60.0, $invoice->balanceDue());

        $this->actingAs($user)->post(route('invoices.payments.store', $invoice), [
            'amount' => '61',
            'paid_at' => '2026-11-27 11:00:00',
            'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->actingAs($user)->post(route('invoices.payments.store', $invoice), [
            'amount' => '60',
            'paid_at' => '2026-11-27 12:00:00',
            'method' => 'cash',
        ])->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(0.0, $invoice->balanceDue());
    }

    public function test_invoice_validation_requires_a_client_and_fee_item(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post(route('invoices.store'), [
            'issue_date' => '2026-11-27',
            'status' => 'draft',
            'currency' => 'USD',
            'items' => [],
        ])->assertSessionHasErrors(['client_id', 'items']);
    }

    public function test_invoice_pages_require_authentication(): void
    {
        $this->get(route('invoices.index'))->assertRedirect(route('login'));
        $this->get(route('invoices.create'))->assertRedirect(route('login'));
        $this->post(route('invoices.store'))->assertRedirect(route('login'));
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Billing User',
            'email' => 'billing-'.uniqid().'@example.com',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
    }

    private function createClient(): Client
    {
        return Client::create([
            'name' => 'Invoice Client',
            'phone' => '0123456789',
        ]);
    }

    private function createCase(Client $client): LegalCase
    {
        return LegalCase::create([
            'case_number' => 'INV-CASE-'.uniqid(),
            'title' => 'Invoice Case',
            'client_id' => $client->id,
            'case_type' => 'Civil',
        ]);
    }

    private function createInvoice(Client $client, User $user): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'client_id' => $client->id,
            'created_by' => $user->id,
            'issue_date' => '2026-11-27',
            'status' => 'sent',
            'currency' => 'USD',
            'subtotal' => 100,
            'tax_total' => 0,
            'total' => 100,
        ]);
        $invoice->items()->create([
            'description' => 'Legal fee',
            'quantity' => 1,
            'unit_price' => 100,
            'tax_percent' => 0,
            'amount' => 100,
        ]);

        return $invoice;
    }
}
