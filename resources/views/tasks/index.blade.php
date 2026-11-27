@extends('layouts.app')

@section('heading', 'Tasks')

@section('content')
    <div class="page-actions mb-5 flex justify-between">
        <div>
            <h1 class="text-2xl font-bold">Tasks</h1>
            <p class="mt-1 text-sm text-slate-600">Track work due dates and case-related follow-ups.</p>
        </div>
        <a class="btn primary" href="{{ route('tasks.create') }}">Create task</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="table w-full text-sm">
            <thead>
                <tr>
                    <th>Due</th>
                    <th>Task</th>
                    <th>Case</th>
                    <th>Assignee</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td>{{ $task->due_at?->format('d M Y H:i') ?? 'No due date' }}</td>
                        <td><a class="font-medium text-teal-700" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                        <td>{{ $task->legalCase?->case_number ?? 'General' }}</td>
                        <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                        <td>{{ ucfirst($task->priority) }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($task->status)) }}</td>
                        <td class="whitespace-nowrap">
                            <a class="text-teal-700" href="{{ route('tasks.edit', $task) }}">Edit</a>
                            <span class="px-1 text-slate-400">|</span>
                            <form class="inline" method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No tasks created.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $tasks->links() }}</div>
    </div>
@endsection
