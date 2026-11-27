@extends('layouts.app')

@section('heading', 'Hearing details')

@section('content')
    <div class="card max-w-3xl">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Hearing</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $hearing->legalCase?->case_number }}</p>
            </div>
            <a class="btn primary" href="{{ $hearing->googleCalendarUrl() }}" target="_blank" rel="noopener noreferrer">Add to Google Calendar</a>
        </div>

        <dl class="grid gap-4 md:grid-cols-2">
            <div><dt class="text-xs uppercase text-slate-500">Case</dt><dd>{{ $hearing->legalCase?->case_number }} - {{ $hearing->legalCase?->title }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">Date and time</dt><dd>{{ $hearing->hearing_date->format('d M Y H:i') }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">Court room</dt><dd>{{ $hearing->court_room ?: '-' }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd>{{ ucfirst($hearing->status) }}</dd></div>
            <div class="md:col-span-2"><dt class="text-xs uppercase text-slate-500">Purpose</dt><dd>{{ $hearing->purpose ?: '-' }}</dd></div>
            <div class="md:col-span-2"><dt class="text-xs uppercase text-slate-500">Outcome / notes</dt><dd>{{ $hearing->outcome ?: '-' }}</dd></div>
        </dl>

        @if($hearing->reminders->isNotEmpty())
            <h2 class="mt-6 mb-2 font-bold">Reminders</h2>
            @foreach($hearing->reminders as $reminder)
                <p class="text-sm">{{ strtoupper($reminder->channel) }}: {{ ucfirst($reminder->status) }} - {{ $reminder->recipient }}</p>
            @endforeach
        @endif
    </div>
@endsection
