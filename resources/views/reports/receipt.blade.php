<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 13px; }
        .receipt { max-width: 700px; margin: 30px auto; border: 2px solid #0f766e; padding: 30px; }
        h1 { color: #0f766e; margin-top: 0; }
        .amount { font-size: 28px; font-weight: bold; color: #0f766e; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="receipt">
        <h1>Payment Receipt</h1>
        <p>Receipt for payment #{{ $payment->id }}</p>
        <p class="amount">{{ number_format($payment->amount, 2) }} {{ $invoice->currency }}</p>
        <table>
            <tr><td>Invoice</td><td>{{ $invoice->invoice_number }}</td></tr>
            <tr><td>Client</td><td>{{ $invoice->client?->name }}</td></tr>
            <tr><td>Case</td><td>{{ $invoice->legalCase?->case_number ?? '-' }}</td></tr>
            <tr><td>Paid at</td><td>{{ $payment->paid_at->format('d M Y H:i') }}</td></tr>
            <tr><td>Method</td><td>{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td></tr>
            <tr><td>Reference</td><td>{{ $payment->reference ?: '-' }}</td></tr>
            <tr><td>Invoice balance</td><td>{{ number_format($invoice->balanceDue(), 2) }} {{ $invoice->currency }}</td></tr>
        </table>
        @if($payment->notes)<p><strong>Notes:</strong> {{ $payment->notes }}</p>@endif
    </div>
</body>
</html>
