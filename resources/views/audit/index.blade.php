@extends('layouts.app')

@section('heading', 'Audit log')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Audit log</h1>
            <p class="mt-1 text-sm text-slate-600">Successful create, update and delete activity.</p>
        </div>
        <form method="GET" action="{{ route('audit.index') }}">
            <select class="field" name="action" onchange="this.form.submit()">
                <option value="">All actions</option>
                @foreach(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="card overflow-x-auto">
        <table class="table w-full text-sm">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Route</th><th>IP</th><th>Details</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? 'Unknown' }}</td>
                        <td>{{ ucfirst($log->action) }}</td>
                        <td>{{ $log->route_name ?: '-' }}</td>
                        <td>{{ $log->ip_address ?: '-' }}</td>
                        <td class="max-w-md truncate">{{ json_encode($log->changes) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No audit activity recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
@endsection
