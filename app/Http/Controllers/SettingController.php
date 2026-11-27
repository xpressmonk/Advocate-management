<?php

namespace App\Http\Controllers;

use App\Support\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.reminders', [
            'emailEnabled' => AppSettings::boolean('email_reminders_enabled', true),
            'mailHost' => AppSettings::get('mail_host', env('MAIL_HOST')),
            'mailPort' => AppSettings::get('mail_port', env('MAIL_PORT', 587)),
            'mailEncryption' => AppSettings::get('mail_encryption', env('MAIL_ENCRYPTION', 'tls')),
            'mailUsername' => AppSettings::get('mail_username', env('MAIL_USERNAME')),
            'mailPasswordSet' => AppSettings::get('mail_password') !== null,
            'mailFromAddress' => AppSettings::get('mail_from_address', env('MAIL_FROM_ADDRESS')),
            'mailFromName' => AppSettings::get('mail_from_name', env('MAIL_FROM_NAME', config('app.name'))),
            'smsEnabled' => AppSettings::boolean('sms_reminders_enabled', true),
            'smsWebhook' => AppSettings::get('sms_webhook_url', env('SMS_WEBHOOK_URL')),
            'smsTokenSet' => AppSettings::get('sms_bearer_token') !== null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_enabled' => ['nullable'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'between:1,65535'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'sms_enabled' => ['nullable'],
            'sms_webhook_url' => ['nullable', 'url', 'max:500'],
            'sms_bearer_token' => ['nullable', 'string', 'max:500'],
        ]);

        AppSettings::set('email_reminders_enabled', $request->boolean('email_enabled') ? '1' : '0');
        AppSettings::set('mail_host', $validated['mail_host'] ?? null);
        AppSettings::set('mail_port', $validated['mail_port'] ?? null);
        AppSettings::set('mail_encryption', $validated['mail_encryption'] ?? null);
        AppSettings::set('mail_username', $validated['mail_username'] ?? null);
        AppSettings::set('mail_from_address', $validated['mail_from_address'] ?? null);
        AppSettings::set('mail_from_name', $validated['mail_from_name'] ?? null);
        AppSettings::set('sms_reminders_enabled', $request->boolean('sms_enabled') ? '1' : '0');
        AppSettings::set('sms_webhook_url', $validated['sms_webhook_url'] ?? null);

        if (! empty($validated['mail_password'])) {
            AppSettings::set('mail_password', $validated['mail_password']);
        }
        if (! empty($validated['sms_bearer_token'])) {
            AppSettings::set('sms_bearer_token', $validated['sms_bearer_token']);
        }

        return back()->with('success', 'Email and SMS reminder settings saved.');
    }
}
