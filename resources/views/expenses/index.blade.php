@extends('layouts.app')

@section('heading', 'Expenses')

@section('content')
    <div class="page-actions mb-5 flex justify-between">
        <div>
            <h1 class="text-2xl font-bold">Expenses</h1>
            <p class="mt-1 text-sm text-slate-600">Track case costs and office spending.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn border border-slate-300 bg-white" href="{{ route('reports.expenses.pdf', request()->only(['case_id', 'status'])) }}">Expense PDF</a>
            <a class="btn primary" href="{{ route('expenses.create') }}">Record expense</a>
        </div>
    </div>

    <form class="card mb-5 grid gap-3 sm:grid-cols-4" method="GET" action="{{ route('expenses.index') }}">
        <label class="text-sm">
            Case
            <select class="field mt-1" name="case_id">
                <option value="">All cases</option>
                @foreach($cases as $case)
                    <option value="{{ $case->id }}" @selected(request('case_id') == $case->id)>{{ $case->case_number }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            Category
            <select class="field mt-1" name="category">
                <option value="">All categories</option>
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
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

    <div class="mb-4 rounded-lg border border-teal-100 bg-teal-50 p-4">
        <span class="text-sm text-teal-800">Filtered expense total:</span>
        <strong class="ml-2 text-lg text-teal-900">{{ number_format($total, 2) }}</strong>
    </div>

    <div class="card overflow-x-auto">
        <table class="table w-full text-sm">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Expense</th>
                    <th>Category</th>
                    <th>Case</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_date->format('d M Y') }}</td>
                        <td><a class="font-medium text-teal-700" href="{{ route('expenses.show', $expense) }}">{{ $expense->title }}</a></td>
                        <td>{{ str_replace('_', ' ', ucfirst($expense->category)) }}</td>
                        <td>{{ $expense->legalCase?->case_number ?? 'Office/general' }}</td>
                        <td>{{ ucfirst($expense->status) }}</td>
                        <td>{{ number_format($expense->amount, 2) }} {{ $expense->currency }}</td>
                        <td class="whitespace-nowrap">
                            <a class="text-teal-700" href="{{ route('expenses.edit', $expense) }}">Edit</a>
                            <span class="px-1 text-slate-400">|</span>
                            <form class="inline" method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No expenses recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $expenses->links() }}</div>
    </div>
@endsection
