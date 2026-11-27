<?php

namespace App\Services;

use App\Mail\HearingReminderMail;
use App\Models\Hearing;
use App\Models\HearingReminder;
use App\Support\AppSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class HearingReminderService
{
    private function emailEnabled(): bool
    {
        return AppSettings::boolean('email_reminders_enabled', true);
    }

    private function configureEmail(): void
    {
        $host = AppSettings::get('mail_host', env('MAIL_HOST'));
        if (! $host) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) AppSettings::get('mail_port', env('MAIL_PORT', 587)),
            'mail.mailers.smtp.encryption' => AppSettings::get('mail_encryption', env('MAIL_ENCRYPTION', 'tls')),
            'mail.mailers.smtp.username' => AppSettings::get('mail_username', env('MAIL_USERNAME')),
            'mail.mailers.smtp.password' => AppSettings::get('mail_password', env('MAIL_PASSWORD')),
            'mail.from.address' => AppSettings::get('mail_from_address', env('MAIL_FROM_ADDRESS')),
            'mail.from.name' => AppSettings::get('mail_from_name', env('MAIL_FROM_NAME', config('app.name'))),
        ]);
    }

    public function createDueReminders(Hearing $hearing): void
    {
        if (! in_array($hearing->status, ['scheduled', 'adjourned'], true) || $hearing->hearing_date <= now()) {
            return;
        }

        $hearing->loadMissing('legalCase.client');
        $client = $hearing->legalCase?->client;
        if (! $client) {
            return;
        }

        if ($client->email && $this->emailEnabled()) {
            HearingReminder::firstOrCreate([
                'hearing_id' => $hearing->id,
                'channel' => 'email',
                'scheduled_for' => $hearing->hearing_date->copy()->subDay()->startOfMinute(),
            ], [
                'recipient' => $client->email,
                'status' => 'pending',
            ]);
        }

        if ($client->phone && AppSettings::boolean('sms_reminders_enabled', true)) {
            HearingReminder::firstOrCreate([
                'hearing_id' => $hearing->id,
                'channel' => 'sms',
                'scheduled_for' => $hearing->hearing_date->copy()->subDay()->startOfMinute(),
            ], [
                'recipient' => $client->phone,
                'status' => 'pending',
            ]);
        }
    }

    public function sendDue(): int
    {
        $sent = 0;
        $reminders = HearingReminder::with('hearing.legalCase.client')
            ->where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->get();

        foreach ($reminders as $reminder) {
            if (! $reminder->hearing || ! in_array($reminder->hearing->status, ['scheduled', 'adjourned'], true)) {
                $reminder->update(['status' => 'cancelled', 'error' => 'Hearing is no longer scheduled.']);
                continue;
            }

            try {
                if ($reminder->channel === 'email') {
                    $this->configureEmail();
                    Mail::to($reminder->recipient)->send(new HearingReminderMail($reminder->hearing));
                } else {
                    $this->sendSms($reminder);
                }

                $reminder->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
                $sent++;
            } catch (Throwable $exception) {
                $reminder->update([
                    'status' => 'failed',
                    'error' => mb_substr($exception->getMessage(), 0, 2000),
                ]);
                Log::warning('Hearing reminder delivery failed.', [
                    'reminder_id' => $reminder->id,
                    'channel' => $reminder->channel,
                ]);
            }
        }

        return $sent;
    }

    public function sendNow(HearingReminder $reminder): void
    {
        $reminder->loadMissing('hearing.legalCase.client');
        try {
            if ($reminder->channel === 'email') {
                $this->configureEmail();
                Mail::to($reminder->recipient)->send(new HearingReminderMail($reminder->hearing));
            } else {
                $this->sendSms($reminder);
            }
            $reminder->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
        } catch (Throwable $exception) {
            $reminder->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            throw $exception;
        }
    }

    private function sendSms(HearingReminder $reminder): void
    {
        if (! AppSettings::boolean('sms_reminders_enabled', true)) {
            throw new \RuntimeException('SMS reminders are disabled.');
        }
        $webhook = AppSettings::get('sms_webhook_url', env('SMS_WEBHOOK_URL'));
        $message = $this->smsMessage($reminder->hearing);

        if (! $webhook) {
            Log::warning('SMS reminder webhook is not configured; message was not delivered.', [
                'reminder_id' => $reminder->id,
                'recipient' => $reminder->recipient,
                'message' => $message,
            ]);
            throw new \RuntimeException('SMS_WEBHOOK_URL is not configured.');
        }

        $request = Http::timeout(15);
        $token = AppSettings::get('sms_bearer_token');
        if ($token) {
            $request = $request->withToken((string) $token);
        }
        $response = $request->post($webhook, [
            'to' => $reminder->recipient,
            'message' => $message,
        ]);
        if (! $response->successful()) {
            throw new \RuntimeException('SMS provider rejected the message (HTTP '.$response->status().').');
        }
    }

    private function emailMessage(Hearing $hearing): string
    {
        return "Hearing reminder\n\n"
            .'Case: '.$hearing->legalCase->case_number.' - '.$hearing->legalCase->title."\n"
            .'Date: '.$hearing->hearing_date->format('d M Y H:i')."\n"
            .'Court room: '.($hearing->court_room ?: 'Not specified')."\n"
            .'Purpose: '.($hearing->purpose ?: 'Not specified')."\n";
    }

    private function smsMessage(Hearing $hearing): string
    {
        return 'Hearing reminder: '.$hearing->legalCase->case_number
            .' on '.$hearing->hearing_date->format('d M Y H:i')
            .($hearing->court_room ? ' at '.$hearing->court_room : '');
    }
}
