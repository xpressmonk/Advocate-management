<?php

namespace Tests\Feature;

use App\Mail\HearingReminderMail;
use App\Models\Client;
use App\Models\Hearing;
use App\Models\HearingReminder;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\HearingReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HearingReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduling_a_hearing_creates_deduplicated_email_and_sms_reminders(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();

        $this->actingAs($user)->post(route('hearings.store'), [
            'case_id' => $case->id,
            'hearing_date' => now()->addDays(3)->format('Y-m-d H:i'),
            'court_room' => 'Room 4',
            'purpose' => 'Status hearing',
            'status' => 'scheduled',
        ])->assertRedirect(route('hearings.index'));

        $hearing = Hearing::firstOrFail();
        $service = app(HearingReminderService::class);
        $service->createDueReminders($hearing);

        $this->assertDatabaseHas('hearing_reminders', [
            'hearing_id' => $hearing->id,
            'channel' => 'email',
            'recipient' => 'client@example.com',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('hearing_reminders', [
            'hearing_id' => $hearing->id,
            'channel' => 'sms',
            'recipient' => '0123456789',
            'status' => 'pending',
        ]);
        $this->assertSame(2, HearingReminder::where('hearing_id', $hearing->id)->count());
    }

    public function test_manual_email_reminder_is_sent_and_marked(): void
    {
        Mail::fake();
        $user = $this->createUser();
        $case = $this->createCase();
        $hearing = Hearing::create([
            'case_id' => $case->id,
            'hearing_date' => now()->addDay(),
            'purpose' => 'Arguments',
            'status' => 'scheduled',
        ]);
        $reminder = HearingReminder::create([
            'hearing_id' => $hearing->id,
            'channel' => 'email',
            'recipient' => 'client@example.com',
            'scheduled_for' => now()->subMinute(),
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('hearings.reminders.send', [$hearing, $reminder]))
            ->assertRedirect();

        Mail::assertSent(HearingReminderMail::class, function (HearingReminderMail $mail) use ($hearing): bool {
            $rendered = $mail->render();

            return $mail->hasTo('client@example.com')
                && str_contains($rendered, 'Add to Google Calendar')
                && str_contains($rendered, 'calendar.google.com/calendar/render');
        });
        $reminder->refresh();
        $this->assertSame('sent', $reminder->status);
        $this->assertNotNull($reminder->sent_at);
    }

    public function test_reminder_route_requires_hearing_match_and_authentication(): void
    {
        $case = $this->createCase();
        $hearing = Hearing::create([
            'case_id' => $case->id,
            'hearing_date' => now()->addDay(),
            'status' => 'scheduled',
        ]);
        $other = Hearing::create([
            'case_id' => $case->id,
            'hearing_date' => now()->addDays(2),
            'status' => 'scheduled',
        ]);
        $reminder = HearingReminder::create([
            'hearing_id' => $other->id,
            'channel' => 'email',
            'recipient' => 'client@example.com',
            'scheduled_for' => now()->subMinute(),
            'status' => 'pending',
        ]);

        $this->post(route('hearings.reminders.send', [$hearing, $reminder]))
            ->assertRedirect(route('login'));

        $this->actingAs($this->createUser())
            ->post(route('hearings.reminders.send', [$hearing, $reminder]))
            ->assertNotFound();
    }

    public function test_due_sms_reminder_without_webhook_is_marked_failed(): void
    {
        $case = $this->createCase();
        $hearing = Hearing::create([
            'case_id' => $case->id,
            'hearing_date' => now()->addDay(),
            'status' => 'scheduled',
        ]);
        $reminder = HearingReminder::create([
            'hearing_id' => $hearing->id,
            'channel' => 'sms',
            'recipient' => '0123456789',
            'scheduled_for' => now()->subMinute(),
            'status' => 'pending',
        ]);

        putenv('SMS_WEBHOOK_URL=');
        app(HearingReminderService::class)->sendDue();
        $reminder->refresh();

        $this->assertSame('failed', $reminder->status);
        $this->assertStringContainsString('SMS_WEBHOOK_URL', (string) $reminder->error);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Reminder User',
            'email' => 'reminder-'.uniqid().'@example.com',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
    }

    private function createCase(): LegalCase
    {
        $client = Client::create([
            'name' => 'Reminder Client',
            'email' => 'client@example.com',
            'phone' => '0123456789',
        ]);

        return LegalCase::create([
            'case_number' => 'REM-'.uniqid(),
            'title' => 'Reminder Case',
            'client_id' => $client->id,
            'case_type' => 'Civil',
        ]);
    }
}
