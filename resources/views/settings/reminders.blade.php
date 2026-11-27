@extends('layouts.app')

@section('heading', 'Reminder settings')

@section('content')
    <div class="max-w-4xl">
        <h1 class="mb-5 text-2xl font-bold">Email & SMS reminder settings</h1>

        <form method="POST" action="{{ route('settings.reminders.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="card space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold">Email reminders</h2>
                        <p class="text-sm text-slate-600">Send hearing reminders through SMTP.</p>
                    </div>
                    <label class="flex min-h-11 items-center gap-2">
                        <input type="checkbox" name="email_enabled" value="1" @checked($emailEnabled)>
                        Enabled
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">SMTP host<input class="field mt-1" name="mail_host" value="{{ old('mail_host', $mailHost) }}" placeholder="smtp.example.com"></label>
                    <label class="block text-sm">SMTP port<input class="field mt-1" type="number" name="mail_port" value="{{ old('mail_port', $mailPort) }}" min="1" max="65535"></label>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">Encryption
                        <select class="field mt-1" name="mail_encryption">
                            @foreach(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('mail_encryption', $mailEncryption) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm">SMTP username<input class="field mt-1" name="mail_username" value="{{ old('mail_username', $mailUsername) }}" autocomplete="off"></label>
                </div>
                <label class="block text-sm">
                    SMTP password
                    <input class="field mt-1" type="password" name="mail_password" placeholder="{{ $mailPasswordSet ? 'Saved securely; enter to replace' : 'Enter SMTP password' }}" autocomplete="new-password">
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">From address<input class="field mt-1" type="email" name="mail_from_address" value="{{ old('mail_from_address', $mailFromAddress) }}" placeholder="reminders@example.com"></label>
                    <label class="block text-sm">From name<input class="field mt-1" name="mail_from_name" value="{{ old('mail_from_name', $mailFromName) }}" placeholder="LegalDesk"></label>
                </div>
            </div>

            <div class="card space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold">SMS reminders</h2>
                        <p class="text-sm text-slate-600">Send a JSON POST to an SMS provider webhook.</p>
                    </div>
                    <label class="flex min-h-11 items-center gap-2">
                        <input type="checkbox" name="sms_enabled" value="1" @checked($smsEnabled)>
                        Enabled
                    </label>
                </div>
                <label class="block text-sm">
                    SMS webhook URL
                    <input class="field mt-1" type="url" name="sms_webhook_url" value="{{ old('sms_webhook_url', $smsWebhook) }}" placeholder="https://provider.example.com/sms">
                </label>
                <label class="block text-sm">
                    Webhook bearer token
                    <input class="field mt-1" type="password" name="sms_bearer_token" placeholder="{{ $smsTokenSet ? 'Saved securely; enter to replace' : 'Optional provider token' }}" autocomplete="new-password">
                </label>
                <p class="rounded bg-slate-50 p-3 text-sm text-slate-600">
                    The webhook receives <code>{ "to": "recipient", "message": "text" }</code>. An Authorization bearer token is added when configured.
                </p>
            </div>

            <button class="btn primary" type="submit">Save reminder settings</button>
        </form>
    </div>
@endsection
