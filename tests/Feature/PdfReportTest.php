<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_can_download_invoice_pdf(): void
    {
        $user = $this->createUser();
        $invoice = $this->createInvoice();

        $response = $this->actingAs($user)->get(route('reports.invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_signed_in_user_can_download_payment_receipt_pdf(): void
    {
        $user = $this->createUser();
        $invoice = $this->createInvoice();
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'recorded_by' => $user->id,
            'amount' => 50,
            'paid_at' => '2026-11-27 10:00:00',
            'method' => 'bank',
            'reference' => 'PDF-001',
        ]);

        $response = $this->actingAs($user)->get(route('reports.receipts.pdf', [$invoice, $payment]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_payment_receipt_rejects_payment_from_another_invoice(): void
    {
        $user = $this->createUser();
        $invoice = $this->createInvoice();
        $other = $this->createInvoice();
        $payment = Payment::create([
            'invoice_id' => $other->id,
            'recorded_by' => $user->id,
            'amount' => 10,
            'paid_at' => '2026-11-27 10:00:00',
            'method' => 'cash',
        ]);

        $this->actingAs($user)
            ->get(route('reports.receipts.pdf', [$invoice, $payment]))
            ->assertNotFound();
    }

    public function test_signed_in_user_can_download_case_and_expense_reports(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();
        Expense::create([
            'case_id' => $case->id,
            'recorded_by' => $user->id,
            'title' => 'Court fee',
            'category' => 'court_fees',
            'amount' => 25,
            'currency' => 'USD',
            'expense_date' => '2026-11-27',
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);

        $caseResponse = $this->actingAs($user)->get(route('reports.cases.pdf', $case));
        $caseResponse->assertOk();
        $caseResponse->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $caseResponse->getContent());

        $expenseResponse = $this->actingAs($user)->get(route('reports.expenses.pdf', ['case_id' => $case->id]));
        $expenseResponse->assertOk();
        $expenseResponse->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $expenseResponse->getContent());
    }

    public function test_pdf_report_routes_require_authentication(): void
    {
        $invoice = $this->createInvoice();
        $case = $this->createCase();

        $this->get(route('reports.invoices.pdf', $invoice))->assertRedirect(route('login'));
        $this->get(route('reports.cases.pdf', $case))->assertRedirect(route('login'));
        $this->get(route('reports.expenses.pdf'))->assertRedirect(route('login'));
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Report User',
            'email' => 'report-'.uniqid().'@example.com',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
    }

    private function createCase(): LegalCase
    {
        $client = Client::create([
            'name' => 'Report Client',
            'phone' => '0123456789',
        ]);

        return LegalCase::create([
            'case_number' => 'PDF-'.uniqid(),
            'title' => 'PDF Case',
            'client_id' => $client->id,
            'case_type' => 'Civil',
        ]);
    }

    private function createInvoice(): Invoice
    {
        $user = $this->createUser();
        $case = $this->createCase();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-PDF-'.uniqid(),
            'client_id' => $case->client_id,
            'case_id' => $case->id,
            'created_by' => $user->id,
            'issue_date' => '2026-11-27',
            'due_date' => '2026-12-27',
            'status' => 'sent',
            'currency' => 'USD',
            'subtotal' => 100,
            'tax_total' => 10,
            'total' => 110,
        ]);
        $invoice->items()->create([
            'description' => 'Legal service',
            'quantity' => 1,
            'unit_price' => 100,
            'tax_percent' => 10,
            'amount' => 110,
        ]);

        return $invoice;
    }
}
