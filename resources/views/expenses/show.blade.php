@extends('layouts.app')

@section('heading', $expense->title)

@section('content')
    <div class="card max-w-3xl">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ $expense->title }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $expense->expense_date->format('d M Y') }}</p>
            </div>
            <a class="btn border border-slate-300 bg-white" href="{{ route('expenses.edit', $expense) }}">Edit expense</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <p><strong>Amount:</strong> {{ number_format($expense->amount, 2) }} {{ $expense->currency }}</p>
            <p><strong>Status:</strong> {{ ucfirst($expense->status) }}</p>
            <p><strong>Category:</strong> {{ str_replace('_', ' ', ucfirst($expense->category)) }}</p>
            <p><strong>Payment method:</strong> {{ str_replace('_', ' ', ucfirst($expense->payment_method)) }}</p>
            <p><strong>Case:</strong>
                @if($expense->legalCase)
                    <a class="text-teal-700" href="{{ route('cases.show', $expense->legalCase) }}">{{ $expense->legalCase->case_number }}</a>
                @else
                    Office/general
                @endif
            </p>
            <p><strong>Vendor:</strong> {{ $expense->vendor ?: '-' }}</p>
            <p><strong>Reference:</strong> {{ $expense->reference ?: '-' }}</p>
            <p><strong>Reimbursable:</strong> {{ $expense->reimbursable ? 'Yes' : 'No' }}</p>
        </div>

        @if($expense->description)
            <div class="mt-5 rounded bg-slate-50 p-3 text-sm">
                <strong>Description:</strong>
                <p class="mt-1">{{ $expense->description }}</p>
            </div>
        @endif

        <p class="mt-5 text-sm text-slate-600">Recorded by {{ $expense->recorder?->name ?? 'Unknown user' }}</p>
    </div>
@endsection
