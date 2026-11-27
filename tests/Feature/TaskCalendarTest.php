<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Hearing;
use App\Models\LegalCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_can_create_update_and_delete_a_case_task(): void
    {
        $creator = $this->createUser('creator@example.com');
        $assignee = $this->createUser('assignee@example.com');
        $case = $this->createCase();

        $this->actingAs($creator)->post(route('tasks.store'), [
            'title' => 'Prepare filing',
            'description' => 'Collect and review documents.',
            'case_id' => $case->id,
            'assigned_to' => $assignee->id,
            'due_at' => '2026-11-28T09:30',
            'priority' => 'high',
            'status' => 'pending',
        ])->assertRedirect();

        $task = Task::firstOrFail();
        $this->assertSame($case->id, $task->case_id);
        $this->assertSame($creator->id, $task->created_by);
        $this->assertSame($assignee->id, $task->assigned_to);

        $this->actingAs($creator)->put(route('tasks.update', $task), [
            'title' => 'File documents',
            'description' => '',
            'case_id' => '',
            'assigned_to' => '',
            'due_at' => '',
            'priority' => 'normal',
            'status' => 'completed',
        ])->assertRedirect(route('tasks.show', $task));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'File documents',
            'case_id' => null,
            'assigned_to' => null,
            'status' => 'completed',
        ]);

        $this->actingAs($creator)->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_general_tasks_can_be_created_without_a_case_or_assignee(): void
    {
        $user = $this->createUser('general@example.com');

        $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Review incoming mail',
            'priority' => 'normal',
            'status' => 'pending',
        ])->assertRedirect();

        $task = Task::firstOrFail();
        $this->assertNull($task->case_id);
        $this->assertNull($task->assigned_to);
        $this->assertSame($user->id, $task->created_by);
    }

    public function test_calendar_combines_due_tasks_and_hearings_for_the_selected_month(): void
    {
        $user = $this->createUser('calendar@example.com');
        $case = $this->createCase();
        Task::create([
            'case_id' => $case->id,
            'created_by' => $user->id,
            'title' => 'Prepare November filing',
            'due_at' => '2026-11-15 10:00:00',
            'priority' => 'normal',
            'status' => 'pending',
        ]);
        Task::create([
            'created_by' => $user->id,
            'title' => 'Prepare December filing',
            'due_at' => '2026-12-01 10:00:00',
            'priority' => 'normal',
            'status' => 'pending',
        ]);
        Task::create([
            'created_by' => $user->id,
            'title' => 'Unscheduled note',
            'priority' => 'low',
            'status' => 'pending',
        ]);
        Hearing::create([
            'case_id' => $case->id,
            'hearing_date' => '2026-11-18 12:00:00',
            'purpose' => 'Status hearing',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)->get(route('calendar.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('November 2026')
            ->assertSee('Prepare November filing')
            ->assertSee('Court hearing')
            ->assertSee($case->case_number)
            ->assertDontSee('Prepare December filing')
            ->assertDontSee('Unscheduled note');
    }

    public function test_task_views_are_available_to_any_signed_in_user(): void
    {
        $creator = $this->createUser('owner@example.com');
        $viewer = $this->createUser('viewer@example.com');
        $task = Task::create([
            'created_by' => $creator->id,
            'title' => 'Shared office task',
            'priority' => 'low',
            'status' => 'pending',
        ]);

        $this->actingAs($viewer)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Shared office task');
    }

    public function test_task_and_calendar_pages_require_authentication(): void
    {
        $this->get(route('tasks.index'))->assertRedirect(route('login'));
        $this->get(route('calendar.index'))->assertRedirect(route('login'));
        $this->post(route('tasks.store'))->assertRedirect(route('login'));
    }

    private function createUser(string $email): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'test-password',
            'role' => 'admin',
        ]);
    }

    private function createCase(): LegalCase
    {
        $client = Client::create([
            'name' => 'Test Client',
            'phone' => '0123456789',
        ]);

        return LegalCase::create([
            'case_number' => 'TASK-001',
            'title' => 'Task Test Case',
            'client_id' => $client->id,
            'case_type' => 'Civil',
        ]);
    }
}
