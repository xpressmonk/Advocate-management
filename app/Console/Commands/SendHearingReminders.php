<?php

namespace App\Console\Commands;

use App\Models\Hearing;
use App\Services\HearingReminderService;
use Illuminate\Console\Command;

class SendHearingReminders extends Command
{
    protected $signature = 'hearings:send-reminders';

    protected $description = 'Create and send scheduled hearing email and SMS reminders';

    public function handle(HearingReminderService $service): int
    {
        $created = 0;
        Hearing::with('legalCase.client')
            ->whereIn('status', ['scheduled', 'adjourned'])
            ->where('hearing_date', '>', now())
            ->each(function (Hearing $hearing) use ($service, &$created): void {
                $before = $hearing->reminders()->count();
                $service->createDueReminders($hearing);
                $created += $hearing->reminders()->count() - $before;
            });

        $sent = $service->sendDue();
        $this->info("Reminder records created: {$created}; delivered: {$sent}");

        return self::SUCCESS;
    }
}
