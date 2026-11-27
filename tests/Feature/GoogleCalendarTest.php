<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Hearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_hearing_generates_google_calendar_template_url(): void
    {
        $case = $this->createCase();
        $hearing = Hearing::create([
            'case_id' => $case->id,
            'hearing_date' => '2026-12-01 14:30:00',
            'court_room' => 'Room 7',
            'purpose' => 'Final arguments',
            'status' => 'scheduled',
        ]);

        $url = $hearing->googleCalendarUrl();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://calendar.google.com/calendar/render?', $url);
        $this->assertSame('TEMPLATE', $query['action']);
        $this->assertSame('Hearing - GC-001', $query['text']);
        $this->assertStringContainsString('20261201T143000Z', $query['dates']);
        $this->assertStringContainsString('Final arguments', $query['details']);
        $this->assertSame('Room 7', $query['location']);
    }

    public function test_hearing_pages_show_google_calendar_action(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();
        $hearing = Hearing::create([
            'case_id' => $case->id,
            'hearing_date' => '2026-12-01 14:30:00',
            'purpose' => 'Status hearing',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)->get(route('hearings.show', $hearing))
            ->assertOk()
            ->assertSee('Add to Google Calendar')
            ->assertSee('calendar.google.com', false);

        $this->actingAs($user)->get(route('hearings.index'))
            ->assertOk()
            ->assertSee('Google Calendar');
    }

    public function test_calendar_event_includes_google_calendar_action(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();
        Hearing::create([
            'case_id' => $case->id,
            'hearing_date' => '2026-12-01 14:30:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)->get(route('calendar.index', ['month' => '2026-12']))
            ->assertOk()
            ->assertSee('Add to Google Calendar')
            ->assertSee('calendar.google.com', false);
    }

    public function test_google_calendar_actions_require_authentication(): void
    {
        $case = $this->createCase();
        $hearing = Hearing::create([
            'case_id' => $case->id,
            'hearing_date' => '2026-12-01 14:30:00',
            'status' => 'scheduled',
        ]);

        $this->get(route('hearings.show', $hearing))->assertRedirect(route('login'));
        $this->get(route('calendar.index'))->assertRedirect(route('login'));
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Calendar User',
            'email' => 'calendar-'.uniqid().'@example.com',
            'password' => 'test-password',
        ]);
    }

    private function createCase(): LegalCase
    {
        $client = Client::create([
            'name' => 'Calendar Client',
            'phone' => '0123456789',
        ]);

        return LegalCase::create([
            'case_number' => 'GC-001',
            'title' => 'Google Calendar Case',
            'client_id' => $client->id,
            'case_type' => 'Civil',
        ]);
    }
}
