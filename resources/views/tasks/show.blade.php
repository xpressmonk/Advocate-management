@extends('layouts.app')

@section('heading', 'Task details')

@section('content')
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ $task->title }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ ucfirst($task->priority) }} priority - {{ str_replace('_', ' ', ucfirst($task->status)) }}</p>
            </div>
            <a class="btn primary" href="{{ route('tasks.edit', $task) }}">Edit task</a>
        </div>

        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs uppercase text-slate-500">Due</dt>
                <dd>{{ $task->due_at?->format('d M Y H:i') ?? 'No due date' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-slate-500">Case</dt>
                <dd>{{ $task->legalCase?->case_number ?? 'General task' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-slate-500">Assigned to</dt>
                <dd>{{ $task->assignee?->name ?? 'Unassigned' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-slate-500">Created by</dt>
                <dd>{{ $task->creator?->name ?? 'Unknown' }}</dd>
            </div>
        </dl>

        <div class="mt-5">
            <h2 class="font-bold">Description</h2>
            <p class="mt-1 whitespace-pre-line">{{ $task->description ?: 'No description provided.' }}</p>
        </div>
    </div>
@endsection
