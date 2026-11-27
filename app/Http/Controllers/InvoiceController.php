<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:draft,sent,partially_paid,paid,cancelled'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ]);

        $invoices = Invoice::with(['client', 'legalCase'])
            ->latest('issue_date')
            ->latest('id');

        if (isset($filters['status'])) {
            $invoices->where('status', $filters['status']);
        }
        if (isset($filters['client_id'])) {
            $invoices->where('client_id', $filters['client_id']);
        }

        return view('invoices.index', [
            'invoices' => $invoices->paginate(20)->withQueryString(),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(Request $request): View
    {
        $filters = $request->validate([
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ]);
        $selectedCase = isset($filters['case_id']) ? LegalCase::find($filters['case_id']) : null;

        return view('invoices.form', $this->formData(
            new Invoice,
            $filters['client_id'] ?? $selectedCase?->client_id,
            $filters['case_id'] ?? null,
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateInvoice($request);

        $invoice = DB::transaction(function () use ($validated, $request): Invoice {
            $invoice = Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'client_id' => $validated['client_id'],
                'case_id' => $validated['case_id'] ?? null,
                'created_by' => $request->user()->id,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => $validated['status'],
                'currency' => strtoupper($validated['currency']),
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncItems($invoice, $validated['items']);
            $this->recalculate($invoice);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['client', 'legalCase', 'items', 'payments.recorder', 'creator']);

        return view('invoices.show', ['invoice' => $invoice, 'methods' => $this->methods()]);
    }

    public function edit(Invoice $invoice): View
    {
        $invoice->load(['items']);

        return view('invoices.form', $this->formData($invoice));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $this->validateInvoice($request);

        DB::transaction(function () use ($validated, $invoice): void {
            $invoice->update([
                'client_id' => $validated['client_id'],
                'case_id' => $validated['case_id'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => $validated['status'],
                'currency' => strtoupper($validated['currency']),
                'notes' => $validated['notes'] ?? null,
            ]);
            $this->syncItems($invoice, $validated['items']);
            $this->recalculate($invoice);
            $invoice->refreshStatus();
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'paid_at' => ['required', 'date'],
            'method' => ['required', 'in:cash,bank,card,mobile,cheque,other'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((float) $validated['amount'] > $invoice->balanceDue()) {
            return back()->withErrors(['amount' => 'Payment cannot exceed the invoice balance.']);
        }

        Payment::create([
            'invoice_id' => $invoice->id,
            'recorded_by' => $request->user()->id,
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'],
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);
        $invoice->refreshStatus();

        return redirect()->route('invoices.show', $invoice)->with('success', 'Payment recorded.');
    }

    public function destroyPayment(Invoice $invoice, Payment $payment): RedirectResponse
    {
        abort_unless($payment->invoice_id === $invoice->id, 404);
        $payment->delete();
        $invoice->refreshStatus();

        return redirect()->route('invoices.show', $invoice)->with('success', 'Payment removed.');
    }

    private function validateInvoice(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', 'in:draft,sent,partially_paid,paid,cancelled'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.tax_percent' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if (! $request->filled('case_id')) {
                return;
            }
            $case = LegalCase::find($request->input('case_id'));
            if ($case && (string) $case->client_id !== (string) $request->input('client_id')) {
                $validator->errors()->add('case_id', 'The selected case does not belong to this client.');
            }
        });

        return $validator->validate();
    }

    private function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $subtotal = round($quantity * $unitPrice, 2);
            $amount = round($subtotal * (1 + ((float) $item['tax_percent'] / 100)), 2);

            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_percent' => $item['tax_percent'],
                'amount' => $amount,
            ]);
        }
    }

    private function recalculate(Invoice $invoice): void
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($invoice->items as $item) {
            $itemSubtotal = round((float) $item->quantity * (float) $item->unit_price, 2);
            $itemTax = round($itemSubtotal * ((float) $item->tax_percent / 100), 2);
            $subtotal += $itemSubtotal;
            $taxTotal += $itemTax;
        }

        $invoice->subtotal = round($subtotal, 2);
        $invoice->tax_total = round($taxTotal, 2);
        $invoice->total = round($subtotal + $taxTotal, 2);
        $invoice->save();
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ym');
        $last = Invoice::where('invoice_number', 'like', $prefix.'-%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');
        $next = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function formData(
        Invoice $invoice,
        ?int $selectedClientId = null,
        ?int $selectedCaseId = null,
    ): array {
        return [
            'title' => $invoice->exists ? 'Edit invoice' : 'Create invoice',
            'action' => $invoice->exists ? route('invoices.update', $invoice) : route('invoices.store'),
            'method' => $invoice->exists ? 'PUT' : 'POST',
            'invoice' => $invoice,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'cases' => LegalCase::with('client:id,name')->orderBy('case_number')->get(['id', 'case_number', 'title', 'client_id']),
            'statuses' => $this->statuses(),
            'selectedClientId' => $selectedClientId,
            'selectedCaseId' => $selectedCaseId,
        ];
    }

    private function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'partially_paid' => 'Partially paid',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
        ];
    }

    private function methods(): array
    {
        return [
            'cash' => 'Cash',
            'bank' => 'Bank transfer',
            'card' => 'Card',
            'mobile' => 'Mobile payment',
            'cheque' => 'Cheque',
            'other' => 'Other',
        ];
    }
}
