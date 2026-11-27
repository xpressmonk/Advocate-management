@extends('layouts.app')

@section('heading', $invoice->invoice_number)

@section('content')
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $invoice->invoice_number }}</h1>
                    <p class="mt-1 text-sm text-slate-600">Issued {{ $invoice->issue_date->format('d M Y') }}{{ $invoice->due_date ? ' · Due '.$invoice->due_date->format('d M Y') : '' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a class="btn border border-slate-300 bg-white" href="{{ route('reports.invoices.pdf', $invoice) }}">Download PDF</a>
                    <a class="btn border border-slate-300 bg-white" href="{{ route('invoices.edit', $invoice) }}">Edit invoice</a>
                </div>
            </div>

            <div class="mb-5 grid gap-3 sm:grid-cols-2">
                <p><strong>Client:</strong> {{ $invoice->client?->name }}</p>
                <p><strong>Case:</strong> {{ $invoice->legalCase?->case_number ?? 'No case linked' }}</p>
                <p><strong>Status:</strong> {{ str_replace('_', ' ', ucfirst($invoice->status)) }}</p>
                <p><strong>Currency:</strong> {{ $invoice->currency }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="table w-full text-sm">
                    <thead>
                        <tr><th>Description</th><th>Qty</th><th>Unit price</th><th>Tax</th><th>Amount</th></tr>
                    </thead>
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
            </div>

            <div class="mt-5 ml-auto max-w-xs space-y-2 text-sm">
                <p class="flex justify-between"><span>Subtotal</span><strong>{{ number_format($invoice->subtotal, 2) }}</strong></p>
                <p class="flex justify-between"><span>Tax</span><strong>{{ number_format($invoice->tax_total, 2) }}</strong></p>
                <p class="flex justify-between border-t pt-2 text-lg"><span>Total</span><strong>{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</strong></p>
                <p class="flex justify-between"><span>Paid</span><strong>{{ number_format($invoice->paidAmount(), 2) }}</strong></p>
                <p class="flex justify-between"><span>Balance</span><strong>{{ number_format($invoice->balanceDue(), 2) }}</strong></p>
            </div>

            @if($invoice->notes)<div class="mt-5 rounded bg-slate-50 p-3 text-sm"><strong>Notes:</strong> {{ $invoice->notes }}</div>@endif
        </div>

        <div class="space-y-5">
            @if($invoice->status !== 'cancelled')
                <div class="card">
                    <h2 class="mb-4 font-bold">Record payment</h2>
                    @if($invoice->balanceDue() > 0)
                        <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" class="space-y-3">
                            @csrf
                            <label class="block text-sm">Amount *<input class="field mt-1" type="number" name="amount" min="0.01" max="{{ $invoice->balanceDue() }}" step="0.01" required></label>
                            <label class="block text-sm">Paid at *<input class="field mt-1" type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\\TH:i') }}" required></label>
                            <label class="block text-sm">Method *
                                <select class="field mt-1" name="method" required>
                                    @foreach($methods as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                                </select>
                            </label>
                            <label class="block text-sm">Reference<input class="field mt-1" name="reference" maxlength="100"></label>
                            <label class="block text-sm">Notes<textarea class="field mt-1" name="notes" rows="2" maxlength="1000"></textarea></label>
                            <button class="btn primary w-full" type="submit">Record payment</button>
                        </form>
                    @else
                        <p class="text-sm text-emerald-700">This invoice is fully paid.</p>
                    @endif
                </div>
            @endif

            <div class="card">
                <h2 class="mb-3 font-bold">Payments</h2>
                @forelse($invoice->payments as $payment)
                    <div class="border-b py-3 text-sm last:border-b-0">
                        <p><strong>{{ number_format($payment->amount, 2) }} {{ $invoice->currency }}</strong> - {{ $payment->method }}</p>
                        <p class="text-slate-600">{{ $payment->paid_at->format('d M Y H:i') }}</p>
                        @if($payment->reference)<p class="text-slate-600">Ref: {{ $payment->reference }}</p>@endif
                        <a class="mt-1 inline-block text-teal-700" href="{{ route('reports.receipts.pdf', [$invoice, $payment]) }}">Download receipt</a>
                        <form method="POST" action="{{ route('invoices.payments.destroy', [$invoice, $payment]) }}" onsubmit="return confirm('Remove this payment?')">
                            @csrf @method('DELETE')
                            <button class="mt-1 text-red-600" type="submit">Remove</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">No payments recorded.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
