<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 12px; }
        h1 { margin: 0 0 6px; color: #0f766e; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border-bottom: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #f1f5f9; }
        .summary { width: 250px; margin-left: auto; }
        .summary td { border: 0; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <h1>LegalDesk Invoice</h1>
    <p><strong>{{ $invoice->invoice_number }}</strong> · {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</p>
    <p>
        <strong>Bill to:</strong> {{ $invoice->client?->name }}<br>
        <span class="muted">{{ $invoice->client?->email }} {{ $invoice->client?->phone }}</span><br>
        <span>Case: {{ $invoice->legalCase?->case_number ?? 'No case linked' }}</span>
    </p>
    <p>
        Issue date: {{ $invoice->issue_date->format('d M Y') }}<br>
        Due date: {{ $invoice->due_date?->format('d M Y') ?? '-' }}
    </p>
    <table>
        <thead><tr><th>Description</th><th>Qty</th><th>Unit price</th><th>Tax</th><th>Amount</th></tr></thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->tax_percent, 2) }}%</td>
                    <td>{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="summary">
        <tr><td>Subtotal</td><td>{{ number_format($invoice->subtotal, 2) }}</td></tr>
        <tr><td>Tax</td><td>{{ number_format($invoice->tax_total, 2) }}</td></tr>
        <tr><td><strong>Total</strong></td><td><strong>{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</strong></td></tr>
        <tr><td>Paid</td><td>{{ number_format($invoice->paidAmount(), 2) }}</td></tr>
        <tr><td><strong>Balance</strong></td><td><strong>{{ number_format($invoice->balanceDue(), 2) }}</strong></td></tr>
    </table>
    @if($invoice->notes)<p><strong>Notes:</strong> {{ $invoice->notes }}</p>@endif
</body>
</html>
