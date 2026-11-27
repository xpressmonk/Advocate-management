@extends('layouts.app')

@section('heading', 'Calendar')

@section('content')
    <div class="page-actions mb-5 flex justify-between">
        <div>
            <h1 class="text-2xl font-bold">{{ $month->format('F Y') }}</h1>
            <p class="mt-1 text-sm text-slate-600">Tasks and court hearings for this month.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn border border-slate-300 bg-white" href="{{ route('calendar.index', ['month' => $previousMonth]) }}">Previous</a>
            <a class="btn border border-slate-300 bg-white" href="{{ route('calendar.index', ['month' => now()->format('Y-m')]) }}">Today</a>
            <a class="btn border border-slate-300 bg-white" href="{{ route('calendar.index', ['month' => $nextMonth]) }}">Next</a>
            <a class="btn primary" href="{{ route('tasks.create') }}">Create task</a>
        </div>
    </div>

    @if($eventsByDate->isEmpty())
        <p class="mb-4 rounded-lg bg-white p-4 text-slate-600">No tasks or hearings are scheduled this month.</p>
    @endif

    <div class="space-y-3 md:hidden">
        @foreach($days as $day)
            @php($dayEvents = $eventsByDate->get($day->format('Y-m-d'), collect()))
            @if($dayEvents->isNotEmpty())
                <section class="card">
                    <h2 class="mb-2 font-bold">{{ $day->format('l, d M') }}</h2>
                    <div class="space-y-2">
                        @foreach($dayEvents as $event)
                            <a class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50" href="{{ $event['url'] }}">
                                <span class="text-xs font-semibold uppercase text-teal-700">{{ $event['type'] }}</span>
                                <span class="block font-medium">{{ $event['date']->format('H:i') }} - {{ $event['title'] }}</span>
                                <span class="block text-sm text-slate-600">{{ $event['detail'] }} - {{ str_replace('_', ' ', $event['status']) }}</span>
                            </a>
                            @if($event['type'] === 'Hearing')
                                <a class="mt-1 inline-block text-sm text-teal-700" href="{{ $event['google_url'] }}" target="_blank" rel="noopener noreferrer">Add to Google Calendar</a>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>

    <div class="hidden overflow-x-auto rounded-xl border border-slate-200 bg-white md:block">
        <div class="grid min-w-[760px] grid-cols-7">
            @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $weekday)
                <div class="border-b border-r bg-slate-50 p-2 text-xs font-semibold uppercase text-slate-600">{{ $weekday }}</div>
            @endforeach
            @foreach($days as $day)
                @php($dayEvents = $eventsByDate->get($day->format('Y-m-d'), collect()))
                <div @class([
                    'min-h-28 border-b border-r p-2',
                    'bg-slate-50 text-slate-400' => $day->format('Y-m') !== $month->format('Y-m'),
                ])>
                    <div class="mb-1 text-sm font-semibold">{{ $day->format('j') }}</div>
                    <div class="space-y-1">
                        @foreach($dayEvents->take(3) as $event)
                            <a @class([
                                'block truncate rounded px-1.5 py-1 text-xs',
                                'bg-teal-50 text-teal-800' => $event['type'] === 'Task',
                                'bg-indigo-50 text-indigo-800' => $event['type'] === 'Hearing',
                            ]) href="{{ $event['url'] }}" title="{{ $event['type'] }}: {{ $event['title'] }}">
                                {{ $event['date']->format('H:i') }} {{ $event['title'] }}
                            </a>
                            @if($event['type'] === 'Hearing')
                                <a class="mb-1 block truncate px-1.5 text-[10px] text-teal-700" href="{{ $event['google_url'] }}" target="_blank" rel="noopener noreferrer">
                                    Add to Google Calendar
                                </a>
                            @endif
                        @endforeach
                        @if($dayEvents->count() > 3)
                            <span class="text-xs text-slate-500">+{{ $dayEvents->count() - 3 }} more</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-4 text-sm text-slate-600">
        <span><span class="mr-1 inline-block h-3 w-3 rounded bg-teal-200"></span>Tasks</span>
        <span><span class="mr-1 inline-block h-3 w-3 rounded bg-indigo-200"></span>Court hearings</span>
    </div>
@endsection
