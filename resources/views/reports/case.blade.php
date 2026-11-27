<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; }
        h1, h2 { color: #0f766e; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 18px; }
        th, td { border-bottom: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>Case Summary</h1>
    <p><strong>{{ $case->case_number }}</strong> - {{ $case->title }}</p>
    <p>Client: {{ $case->client?->name }} · Status: {{ ucfirst($case->status) }} · Type: {{ $case->case_type }}</p>
    <p>Court: {{ $case->court_name ?: '-' }} · Next hearing: {{ $case->next_hearing_date?->format('d M Y') ?: '-' }}</p>
    @if($case->description)<p>{{ $case->description }}</p>@endif

    <h2>Hearings</h2>
    <table><thead><tr><th>Date</th><th>Purpose</th><th>Status</th></tr></thead><tbody>
    @forelse($case->hearings as $hearing)<tr><td>{{ $hearing->hearing_date->format('d M Y H:i') }}</td><td>{{ $hearing->purpose }}</td><td>{{ $hearing->status }}</td></tr>@empty<tr><td colspan="3">No hearings.</td></tr>@endforelse
    </tbody></table>

    <h2>Tasks</h2>
    <table><thead><tr><th>Task</th><th>Due</th><th>Status</th></tr></thead><tbody>
    @forelse($case->tasks as $task)<tr><td>{{ $task->title }}</td><td>{{ $task->due_at?->format('d M Y H:i') ?: '-' }}</td><td>{{ str_replace('_', ' ', $task->status) }}</td></tr>@empty<tr><td colspan="3">No tasks.</td></tr>@endforelse
    </tbody></table>

    <h2>Invoices</h2>
    <table><thead><tr><th>Invoice</th><th>Total</th><th>Status</th></tr></thead><tbody>
    @forelse($case->invoices as $invoice)<tr><td>{{ $invoice->invoice_number }}</td><td>{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td><td>{{ str_replace('_', ' ', $invoice->status) }}</td></tr>@empty<tr><td colspan="3">No invoices.</td></tr>@endforelse
    </tbody></table>

    <h2>Expenses</h2>
    <table><thead><tr><th>Expense</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>
    @forelse($case->expenses as $expense)<tr><td>{{ $expense->title }}</td><td>{{ $expense->expense_date->format('d M Y') }}</td><td>{{ number_format($expense->amount, 2) }} {{ $expense->currency }}</td><td>{{ $expense->status }}</td></tr>@empty<tr><td colspan="4">No expenses.</td></tr>@endforelse
    </tbody></table>
</body>
</html>
