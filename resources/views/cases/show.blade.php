@extends('layouts.app')

@section('heading', 'Case details')

@section('content')
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-2xl font-bold">{{ $case->case_number }} - {{ $case->title }}</h1>
            <div class="flex flex-wrap gap-2">
                <a class="btn border border-slate-300 bg-white" href="{{ route('tasks.create', ['case_id' => $case->id]) }}">Add task</a>
                <a class="btn border border-slate-300 bg-white" href="{{ route('invoices.create', ['case_id' => $case->id]) }}">Create invoice</a>
                <a class="btn border border-slate-300 bg-white" href="{{ route('expenses.create', ['case_id' => $case->id]) }}">Record expense</a>
                <a class="btn border border-slate-300 bg-white" href="{{ route('reports.cases.pdf', $case) }}">Download case PDF</a>
                <a class="btn primary" href="{{ route('documents.index', ['case_id' => $case->id]) }}">Manage documents</a>
            </div>
        </div>
        <p class="mt-3">Client: {{ $case->client?->name }}</p>
        <p>Court: {{ $case->court_name }}</p>
        <p>Type: {{ $case->case_type }}</p>
        <p>Status: {{ $case->status }}</p>
        <p>Next hearing: {{ $case->next_hearing_date?->format('d M Y') }}</p>
        <p class="mt-3">{{ $case->description }}</p>

        <h2 class="mt-5 font-bold">Assigned advocates</h2>
        @forelse($case->advocates as $advocate)
            <p>{{ $advocate->name }}</p>
        @empty
            <p>None assigned.</p>
        @endforelse

        <h2 class="mt-5 font-bold">Hearings</h2>
        @forelse($case->hearings as $hearing)
            <p>
                {{ $hearing->hearing_date->format('d M Y H:i') }} - {{ $hearing->purpose }} - {{ $hearing->status }}
                - <a class="text-teal-700" href="{{ $hearing->googleCalendarUrl() }}" target="_blank" rel="noopener noreferrer">Add to Google Calendar</a>
            </p>
        @empty
            <p>No hearing records.</p>
        @endforelse

        <h2 class="mt-5 font-bold">Tasks</h2>
        @forelse($case->tasks as $task)
            <p>
                <a class="text-teal-700" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>
                - {{ str_replace('_', ' ', $task->status) }}
                - {{ $task->due_at?->format('d M Y H:i') ?? 'No due date' }}
            </p>
        @empty
            <p>No tasks for this case.</p>
        @endforelse

        <h2 class="mt-5 font-bold">Invoices</h2>
        @forelse($case->invoices as $invoice)
            <p>
                <a class="text-teal-700" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                - {{ number_format($invoice->total, 2) }} {{ $invoice->currency }}
                - {{ str_replace('_', ' ', ucfirst($invoice->status)) }}
            </p>
        @empty
            <p>No invoices for this case.</p>
        @endforelse

        <h2 class="mt-5 font-bold">Expenses</h2>
        @forelse($case->expenses as $expense)
            <p>
                <a class="text-teal-700" href="{{ route('expenses.show', $expense) }}">{{ $expense->title }}</a>
                - {{ number_format($expense->amount, 2) }} {{ $expense->currency }}
                - {{ ucfirst($expense->status) }}
            </p>
        @empty
            <p>No expenses for this case.</p>
        @endforelse

        <h2 class="mt-5 font-bold">Documents</h2>
        @forelse($case->documents as $document)
            <p><a class="text-teal-700" href="{{ route('documents.download', $document) }}">{{ $document->original_name }}</a></p>
        @empty
            <p>No documents uploaded.</p>
        @endforelse
    </div>
@endsection
