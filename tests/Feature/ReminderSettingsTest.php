<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Support\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_can_update_email_and_sms_settings(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->put(route('settings.reminders.update'), [
            'email_enabled' => '1',
            'mail_host' => 'smtp.example.com',
            'mail_port' => '587',
            'mail_encryption' => 'tls',
            'mail_username' => 'reminder@example.com',
            'mail_password' => 'secret-mail-password',
            'mail_from_address' => 'reminders@example.com',
            'mail_from_name' => 'Legal Desk',
            'sms_enabled' => '1',
            'sms_webhook_url' => 'https://sms.example.com/send',
            'sms_bearer_token' => 'secret-sms-token',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(AppSettings::boolean('email_reminders_enabled'));
        $this->assertTrue(AppSettings::boolean('sms_reminders_enabled'));
        $this->assertSame('smtp.example.com', AppSettings::get('mail_host'));
        $this->assertSame('587', AppSettings::get('mail_port'));
        $this->assertSame('https://sms.example.com/send', AppSettings::get('sms_webhook_url'));
        $this->assertSame('secret-mail-password', AppSettings::get('mail_password'));
        $this->assertSame('secret-sms-token', AppSettings::get('sms_bearer_token'));

        $this->assertDatabaseHas('app_settings', ['key' => 'mail_host']);
        $this->assertDatabaseHas('app_settings', ['key' => 'sms_webhook_url']);
    }

    public function test_reminder_passwords_are_not_displayed_in_settings_values(): void
    {
        $user = $this->createUser();
        AppSettings::set('mail_password', 'mail-secret');
        AppSettings::set('sms_bearer_token', 'sms-secret');

        $response = $this->actingAs($user)->get(route('settings.reminders.edit'));

        $response->assertOk();
        $response->assertSee('Saved securely');
        $response->assertDontSee('mail-secret');
        $response->assertDontSee('sms-secret');
    }

    public function test_reminder_settings_require_authentication(): void
    {
        $this->get(route('settings.reminders.edit'))->assertRedirect(route('login'));
        $this->put(route('settings.reminders.update'), [])
            ->assertRedirect(route('login'));
    }

    public function test_reminder_settings_reject_invalid_smtp_and_sms_urls(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->put(route('settings.reminders.update'), [
            'mail_from_address' => 'not-an-email',
            'mail_port' => '99999',
            'mail_encryption' => 'invalid',
            'sms_webhook_url' => 'not-a-url',
        ])->assertSessionHasErrors([
            'mail_port',
            'mail_encryption',
            'mail_from_address',
            'sms_webhook_url',
        ]);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Settings User',
            'email' => 'settings-'.uniqid().'@example.com',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
    }
}
