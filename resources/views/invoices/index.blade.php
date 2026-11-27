@extends('layouts.app')

@section('heading', 'Invoices')

@section('content')
    <div class="page-actions mb-5 flex justify-between">
        <div>
            <h1 class="text-2xl font-bold">Invoices</h1>
            <p class="mt-1 text-sm text-slate-600">Track legal fees, invoice balances and payments.</p>
        </div>
        <a class="btn primary" href="{{ route('invoices.create') }}">Create invoice</a>
    </div>

    <form class="card mb-5 grid gap-3 sm:grid-cols-3" method="GET" action="{{ route('invoices.index') }}">
        <label class="text-sm">
            Client
            <select class="field mt-1" name="client_id">
                <option value="">All clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            Status
            <select class="field mt-1" name="status">
                <option value="">All statuses</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end"><button class="btn primary w-full" type="submit">Filter</button></div>
    </form>

    <div class="card overflow-x-auto">
        <table class="table w-full text-sm">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Client</th>
                    <th>Case</th>
                    <th>Issue date</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><a class="font-medium text-teal-700" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                        <td>{{ $invoice->client?->name }}</td>
                        <td>{{ $invoice->legalCase?->case_number ?? '-' }}</td>
                        <td>{{ $invoice->issue_date->format('d M Y') }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($invoice->status)) }}</td>
                        <td>{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
                        <td class="whitespace-nowrap">
                            <a class="text-teal-700" href="{{ route('invoices.edit', $invoice) }}">Edit</a>
                            <span class="px-1 text-slate-400">|</span>
                            <form class="inline" method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Delete this invoice and its payments?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No invoices created.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </div>
@endsection
