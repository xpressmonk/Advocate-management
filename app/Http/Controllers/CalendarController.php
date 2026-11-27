<?php

namespace App\Http\Controllers;

use App\Models\Hearing;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = Carbon::parse(($filters['month'] ?? now()->format('Y-m')).'-01')->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $tasks = Task::with(['legalCase', 'assignee'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$start, $end])
            ->get();
        $hearings = Hearing::with('legalCase')
            ->whereBetween('hearing_date', [$start, $end])
            ->get();

        $events = new Collection;

        foreach ($tasks as $task) {
            $events->push([
                'type' => 'Task',
                'date' => $task->due_at,
                'title' => $task->title,
                'detail' => $task->legalCase?->case_number ?? 'General task',
                'status' => $task->status,
                'url' => route('tasks.show', $task),
            ]);
        }

        foreach ($hearings as $hearing) {
            $events->push([
                'type' => 'Hearing',
                'date' => $hearing->hearing_date,
                'title' => 'Court hearing',
                'detail' => $hearing->legalCase?->case_number ?? 'Case unavailable',
                'status' => $hearing->status,
                'url' => route('hearings.show', $hearing),
                'google_url' => $hearing->googleCalendarUrl(),
            ]);
        }

        $eventsByDate = $events
            ->sortBy(static fn (array $event): int => $event['date']->timestamp)
            ->groupBy(static fn (array $event): string => $event['date']->format('Y-m-d'));
        $calendarStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $end->copy()->endOfWeek(Carbon::SATURDAY);
        $days = collect();

        for ($date = $calendarStart->copy(); $date->lte($calendarEnd); $date->addDay()) {
            $days->push($date->copy());
        }

        return view('calendar.index', [
            'month' => $month,
            'previousMonth' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            'days' => $days,
            'eventsByDate' => $eventsByDate,
        ]);
    }
}
