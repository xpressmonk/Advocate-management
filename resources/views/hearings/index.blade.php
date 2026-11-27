@extends('layouts.app') @section('heading','Hearings') @section('content')
<div class="flex justify-between mb-5"><h1 class="text-2xl font-bold">Court hearings</h1><a class="btn primary" href="{{ route('hearings.create') }}">Schedule hearing</a></div>
<div class="card overflow-x-auto"><table class="table w-full text-sm"><thead><tr><th>Date</th><th>Case</th><th>Room</th><th>Purpose</th><th>Status</th><th>Reminders</th><th></th></tr></thead><tbody>
@forelse($items as $i)<tr><td>{{ $i->hearing_date->format('d M Y H:i') }}</td><td>{{ $i->legalCase?->case_number }}</td><td>{{ $i->court_room }}</td><td>{{ $i->purpose }}</td><td>{{ $i->status }}</td>
<td>
@forelse($i->reminders as $reminder)
<span class="block">{{ strtoupper($reminder->channel) }}: {{ str_replace('_',' ',ucfirst($reminder->status)) }}</span>
@if($reminder->status !== 'sent')
<form class="inline" method="POST" action="{{ route('hearings.reminders.send',[$i,$reminder]) }}">@csrf<button class="text-teal-700">Send now</button></form>
@endif
@empty
<form class="inline" method="POST" action="{{ route('hearings.reminders.configure',$i) }}">@csrf<button class="text-teal-700">Configure</button></form>
@endforelse
</td>
<td><a href="{{ route('hearings.show',$i) }}">View</a> · <a href="{{ $i->googleCalendarUrl() }}" target="_blank" rel="noopener noreferrer">Google Calendar</a> · <a href="{{ route('hearings.edit',$i) }}">Edit</a> · <form class="inline" method="POST" action="{{ route('hearings.destroy',$i) }}">@csrf @method('DELETE')<button class="text-red-600">Delete</button></form></td></tr>
@empty<tr><td colspan="7">No hearings.</td></tr>@endforelse</tbody></table>{{ $items->links() }}</div>
@endsection
