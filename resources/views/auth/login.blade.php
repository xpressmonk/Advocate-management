<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>LegalDesk Login</title>
    <style>
        .field { box-sizing: border-box; width: 100%; border: 1px solid #cbd5e1; border-radius: 9px; padding: 10px; font-size: 16px; }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-900 p-4 sm:p-6">
    <div class="w-full max-w-md rounded-2xl bg-white p-5 sm:p-8">
        <div class="text-3xl text-teal-700">⚖</div>
        <h1 class="mt-3 text-2xl font-bold">Welcome back</h1>
        <p class="mb-6 text-slate-500">Sign in to your legal workspace.</p>
        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf
            <label class="block">Email<input class="field mt-1" type="email" name="email" value="{{ old('email') }}" required></label>
            <label class="block">Password<input class="field mt-1" type="password" name="password" required></label>
            <label><input type="checkbox" name="remember"> Remember me</label>
            @if($errors->any())<p class="text-red-600">{{ $errors->first() }}</p>@endif
            <button class="min-h-11 w-full rounded-lg bg-teal-700 p-3 text-white">Sign in</button>
        </form>
    </div>
</body>
</html>
