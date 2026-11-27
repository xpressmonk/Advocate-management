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
                    Task title *
                    <input class="field mt-1" name="title" value="{{ old('title', $task->title) }}" maxlength="200" required>
                </label>

                <label class="block text-sm">
                    Case
                    <select class="field mt-1" name="case_id">
                        <option value="">General task (not linked to a case)</option>
                        @foreach($cases as $case)
                            <option value="{{ $case->id }}" @selected((string) old('case_id', $task->case_id ?? $selectedCaseId) === (string) $case->id)>
                                {{ $case->case_number }} - {{ $case->title }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    Assign to
                    <select class="field mt-1" name="assigned_to">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('assigned_to', $task->assigned_to) === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        Due date and time
                        <input class="field mt-1" type="datetime-local" name="due_at" value="{{ old('due_at', $task->due_at?->format('Y-m-d\\TH:i')) }}">
                    </label>
                    <label class="block text-sm">
                        Priority
                        <select class="field mt-1" name="priority" required>
                            @foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', $task->priority ?: 'normal') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="block text-sm">
                    Status
                    <select class="field mt-1" name="status" required>
                        @foreach(['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $task->status ?: 'pending') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    Description
                    <textarea class="field mt-1" name="description" rows="4" maxlength="5000">{{ old('description', $task->description) }}</textarea>
                </label>

                <button class="btn primary" type="submit">Save task</button>
            </form>
        </div>
    </div>
@endsection
