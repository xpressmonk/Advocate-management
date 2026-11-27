@extends('layouts.app')

@section('heading', $title)

@section('content')
    <div class="max-w-3xl">
        <h1 class="mb-5 text-2xl font-bold">{{ $title }}</h1>
        <div class="card">
            <form method="POST" action="{{ $action }}" class="space-y-4">
                @csrf
                @if($method === 'PUT') @method('PUT') @endif

                <label class="block text-sm">
                    Expense title *
                    <input class="field mt-1" name="title" value="{{ old('title', $expense->title) }}" maxlength="200" required>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        Case
                        <select class="field mt-1" name="case_id">
                            <option value="">Office/general expense</option>
                            @foreach($cases as $case)
                                <option value="{{ $case->id }}" @selected((string) old('case_id', $expense->case_id ?? $selectedCaseId) === (string) $case->id)>
                                    {{ $case->case_number }} - {{ $case->title }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm">
                        Category *
                        <select class="field mt-1" name="category" required>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', $expense->category ?: 'other') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="block text-sm">
                        Amount *
                        <input class="field mt-1" type="number" name="amount" value="{{ old('amount', $expense->amount) }}" min="0.01" step="0.01" required>
                    </label>
                    <label class="block text-sm">
                        Currency *
                        <input class="field mt-1 uppercase" name="currency" value="{{ old('currency', $expense->currency ?: 'USD') }}" maxlength="3" required>
                    </label>
                    <label class="block text-sm">
                        Expense date *
                        <input class="field mt-1" type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        Payment method *
                        <select class="field mt-1" name="payment_method" required>
                            @foreach($paymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method', $expense->payment_method ?: 'cash') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm">
                        Status *
                        <select class="field mt-1" name="status" required>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $expense->status ?: 'paid') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        Vendor
                        <input class="field mt-1" name="vendor" value="{{ old('vendor', $expense->vendor) }}" maxlength="200">
                    </label>
                    <label class="block text-sm">
                        Reference
                        <input class="field mt-1" name="reference" value="{{ old('reference', $expense->reference) }}" maxlength="100">
                    </label>
                </div>

                <label class="flex min-h-11 items-center gap-3 text-sm">
                    <input type="checkbox" name="reimbursable" value="1" @checked(old('reimbursable', $expense->reimbursable))>
                    Reimbursable expense
                </label>

                <label class="block text-sm">
                    Description
                    <textarea class="field mt-1" name="description" rows="4" maxlength="5000">{{ old('description', $expense->description) }}</textarea>
                </label>

                <button class="btn primary" type="submit">Save expense</button>
            </form>
        </div>
    </div>
@endsection
