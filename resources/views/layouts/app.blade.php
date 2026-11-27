<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'LegalDesk')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .field { box-sizing: border-box; width: 100%; border: 1px solid #cbd5e1; border-radius: 9px; padding: 10px; font-size: 16px; }
        .btn { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: 10px 15px; border-radius: 9px; font-weight: 600; }
        .primary { background: #0f766e; color: white; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; }
        .table { min-width: 680px; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { font-size: 11px; text-transform: uppercase; color: #64748b; background: #f8fafc; }
        summary::-webkit-details-marker { display: none; }
        @media (max-width: 639px) {
            .page-content > .flex.justify-between { flex-direction: column; align-items: flex-start; gap: 12px; }
            .card { padding: 14px; }
            .table th, .table td { padding: 10px; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen md:flex">
        <aside class="hidden w-64 shrink-0 bg-slate-900 p-5 text-white md:block">
            <h1 class="mb-7 text-xl font-bold">⚖ LegalDesk</h1>
            <nav aria-label="Primary navigation" class="space-y-3 text-sm">
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('cases.index') }}">Cases</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('clients.index') }}">Clients</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('advocates.index') }}">Advocates</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('hearings.index') }}">Hearings</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('documents.index') }}">Documents</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('tasks.index') }}">Tasks</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('calendar.index') }}">Calendar</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('invoices.index') }}">Invoices</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('expenses.index') }}">Expenses</a>
                <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('settings.reminders.edit') }}">Reminder settings</a>
                @if(auth()->user()->canManageUsers())
                    <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('settings.google-drive') }}">Google Drive settings</a>
                @endif
                @if(auth()->user()->canManageUsers())
                    <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('users.index') }}">Users & roles</a>
                    <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('roles.index') }}">Roles</a>
                @endif
                @if(in_array(auth()->user()->role,['admin','manager'],true))
                    <a class="block rounded px-2 py-1 hover:bg-slate-800" href="{{ route('audit.index') }}">Audit log</a>
                @endif
            </nav>
        </aside>
        <main class="min-w-0 flex-1">
            <header class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b bg-white px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <details class="relative md:hidden">
                        <summary aria-label="Toggle navigation menu" class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100">
                            <span class="sr-only">Navigation menu</span>
                            <svg aria-hidden="true" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </summary>
                        <nav aria-label="Primary navigation" class="absolute left-0 top-full z-50 mt-2 w-56 rounded-lg bg-slate-900 p-2 text-sm text-white shadow-xl">
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('dashboard') }}">Dashboard</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('cases.index') }}">Cases</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('clients.index') }}">Clients</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('advocates.index') }}">Advocates</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('hearings.index') }}">Hearings</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('documents.index') }}">Documents</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('tasks.index') }}">Tasks</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('calendar.index') }}">Calendar</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('invoices.index') }}">Invoices</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('expenses.index') }}">Expenses</a>
                            <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('settings.reminders.edit') }}">Reminder settings</a>
                            @if(auth()->user()->canManageUsers())
                                <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('settings.google-drive') }}">Google Drive settings</a>
                            @endif
                            @if(auth()->user()->canManageUsers())
                                <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('users.index') }}">Users & roles</a>
                                <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('roles.index') }}">Roles</a>
                            @endif
                            @if(in_array(auth()->user()->role,['admin','manager'],true))
                                <a class="block rounded-lg px-3 py-2 hover:bg-slate-700" href="{{ route('audit.index') }}">Audit log</a>
                            @endif
                        </nav>
                    </details>
                    <b class="truncate">@yield('heading', 'Dashboard')</b>
                </div>
                <div class="flex min-w-0 items-center gap-3 text-sm">
                    <span class="max-w-[55vw] break-words sm:max-w-none">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="min-h-11 px-2 text-teal-700">Sign out</button></form>
                </div>
            </header>
            <div class="page-content mx-auto w-full max-w-7xl px-3 py-4 sm:px-5 sm:py-6 md:p-8">
                @if(session('success'))<p class="mb-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('success') }}</p>@endif
                @if(session('error'))<p class="mb-4 rounded bg-red-50 p-3 text-red-700">{{ session('error') }}</p>@endif
                @if($errors->any())<div class="mb-4 rounded bg-red-50 p-3 text-red-700">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
