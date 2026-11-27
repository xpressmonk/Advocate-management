<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    private function formData(Task $task, ?int $selectedCaseId = null): array
    {
        return [
            'title' => $task->exists ? 'Edit task' : 'Create task',
            'action' => $task->exists ? route('tasks.update', $task) : route('tasks.store'),
            'method' => $task->exists ? 'PUT' : 'POST',
            'task' => $task,
            'cases' => LegalCase::orderBy('case_number')->get(),
            'users' => User::orderBy('name')->get(),
            'selectedCaseId' => $task->case_id ?? $selectedCaseId,
        ];
    }

    public function index(): View
    {
        $tasks = Task::with(['legalCase', 'assignee'])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('id')
            ->paginate(20);

        return view('tasks.index', ['tasks' => $tasks]);
    }

    public function create(Request $request): View
    {
        $filters = $request->validate([
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
        ]);

        return view('tasks.form', $this->formData(new Task, $filters['case_id'] ?? null));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['created_by'] = $request->user()->id;
        $task = Task::create($validated);

        return redirect()->route('tasks.show', $task)->with('success', 'Task created.');
    }

    public function show(Task $task): View
    {
        $task->load(['legalCase', 'assignee', 'creator']);

        return view('tasks.show', ['task' => $task]);
    }

    public function edit(Task $task): View
    {
        return view('tasks.form', $this->formData($task));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $task->update($request->validate($this->rules()));

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,normal,high'],
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
        ];
    }
}
