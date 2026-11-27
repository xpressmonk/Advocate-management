<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; }
        h1 { color: #0f766e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #cbd5e1; padding: 7px; text-align: left; }
        th { background: #f1f5f9; }
        .total { margin-top: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <h1>Expense Report</h1>
    <table>
        <thead><tr><th>Date</th><th>Title</th><th>Category</th><th>Case</th><th>Status</th><th>Amount</th></tr></thead>
        <tbody>
            @foreach($expenses as $expense)
                <tr>
                    <td>{{ $expense->expense_date->format('d M Y') }}</td>
                    <td>{{ $expense->title }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($expense->category)) }}</td>
                    <td>{{ $expense->legalCase?->case_number ?? 'Office/general' }}</td>
                    <td>{{ ucfirst($expense->status) }}</td>
                    <td>{{ number_format($expense->amount, 2) }} {{ $expense->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="total"><strong>Total:</strong>
        @php
            $totalByCurrency = $expenses->groupBy('currency')->map(fn ($items) => $items->sum('amount'));
        @endphp
        @forelse($totalByCurrency as $currency => $total)
            {{ number_format($total, 2) }} {{ $currency }}@unless($loop->last) · @endunless
        @empty
            0.00
        @endforelse
    </p>
</body>
</html>
