<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\Payment;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function invoice(Invoice $invoice): Response
    {
        $invoice->load(['client', 'legalCase', 'items', 'payments']);

        return $this->downloadPdf(
            'reports.invoice',
            ['invoice' => $invoice],
            $invoice->invoice_number.'.pdf',
        );
    }

    public function receipt(Invoice $invoice, Payment $payment): Response
    {
        abort_unless($payment->invoice_id === $invoice->id, 404);
        $invoice->load(['client', 'legalCase']);

        return $this->downloadPdf(
            'reports.receipt',
            ['invoice' => $invoice, 'payment' => $payment],
            'receipt-'.$invoice->invoice_number.'-'.$payment->id.'.pdf',
        );
    }

    public function caseSummary(LegalCase $case): Response
    {
        $case->load([
            'client',
            'advocates',
            'hearings',
            'tasks',
            'invoices.items',
            'expenses',
        ]);

        return $this->downloadPdf(
            'reports.case',
            ['case' => $case],
            'case-'.$case->case_number.'-summary.pdf',
        );
    }

    public function expenseReport(Request $request): Response
    {
        $filters = $request->validate([
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
            'status' => ['nullable', 'in:paid,pending,cancelled'],
        ]);

        $expenses = Expense::with('legalCase');
        foreach (['case_id', 'status'] as $field) {
            if (isset($filters[$field])) {
                $expenses->where($field, $filters[$field]);
            }
        }
        $expenses = $expenses->latest('expense_date')->get();

        return $this->downloadPdf(
            'reports.expenses',
            ['expenses' => $expenses, 'filters' => $filters],
            'expense-report.pdf',
        );
    }

    private function downloadPdf(string $view, array $data, string $filename): Response
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data)->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
