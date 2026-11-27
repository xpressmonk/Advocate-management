<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
            'category' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'in:paid,pending,cancelled'],
        ]);

        $expenses = Expense::with(['legalCase', 'recorder'])->latest('expense_date')->latest('id');

        foreach (['case_id', 'category', 'status'] as $field) {
            if (isset($filters[$field])) {
                $expenses->where($field, $filters[$field]);
            }
        }

        $filtered = (clone $expenses);
        $total = (float) $filtered->sum('amount');

        return view('expenses.index', [
            'expenses' => $expenses->paginate(20)->withQueryString(),
            'cases' => LegalCase::orderBy('case_number')->get(['id', 'case_number', 'title']),
            'categories' => $this->categories(),
            'statuses' => $this->statuses(),
            'total' => $total,
        ]);
    }

    public function create(Request $request): View
    {
        $filters = $request->validate([
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
        ]);

        return view('expenses.form', $this->formData(new Expense, $filters['case_id'] ?? null));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['currency'] = strtoupper($validated['currency']);
        $validated['reimbursable'] = $request->boolean('reimbursable');
        $expense = Expense::create([
            ...$validated,
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense): View
    {
        $expense->load(['legalCase', 'recorder']);

        return view('expenses.show', ['expense' => $expense]);
    }

    public function edit(Expense $expense): View
    {
        return view('expenses.form', $this->formData($expense));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['currency'] = strtoupper($validated['currency']);
        $validated['reimbursable'] = $request->boolean('reimbursable');
        $expense->update($validated);

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    private function formData(Expense $expense, ?int $selectedCaseId = null): array
    {
        return [
            'title' => $expense->exists ? 'Edit expense' : 'Record expense',
            'action' => $expense->exists ? route('expenses.update', $expense) : route('expenses.store'),
            'method' => $expense->exists ? 'PUT' : 'POST',
            'expense' => $expense,
            'cases' => LegalCase::orderBy('case_number')->get(['id', 'case_number', 'title']),
            'categories' => $this->categories(),
            'paymentMethods' => $this->paymentMethods(),
            'statuses' => $this->statuses(),
            'selectedCaseId' => $selectedCaseId,
        ];
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys($this->categories()))],
            'vendor' => ['nullable', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'currency' => ['required', 'string', 'size:3'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank,card,mobile,cheque,other'],
            'reference' => ['nullable', 'string', 'max:100'],
            'reimbursable' => ['sometimes', 'boolean'],
            'status' => ['required', 'in:paid,pending,cancelled'],
        ];
    }

    private function categories(): array
    {
        return [
            'court_fees' => 'Court fees',
            'filing_fees' => 'Filing fees',
            'travel' => 'Travel',
            'client_meeting' => 'Client meeting',
            'office_supplies' => 'Office supplies',
            'research' => 'Research',
            'postage' => 'Postage',
            'expert_fee' => 'Expert fee',
            'other' => 'Other',
        ];
    }

    private function paymentMethods(): array
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

    private function statuses(): array
    {
        return [
            'paid' => 'Paid',
            'pending' => 'Pending',
            'cancelled' => 'Cancelled',
        ];
    }
}
