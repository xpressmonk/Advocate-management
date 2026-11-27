<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_can_create_update_and_delete_a_case_expense(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();

        $this->actingAs($user)->post(route('expenses.store'), [
            'title' => 'Court filing',
            'description' => 'Municipal court filing charge',
            'case_id' => $case->id,
            'category' => 'court_fees',
            'vendor' => 'Court Registry',
            'amount' => '125.50',
            'currency' => 'usd',
            'expense_date' => '2026-11-27',
            'payment_method' => 'bank',
            'reference' => 'REF-001',
            'reimbursable' => '1',
            'status' => 'paid',
        ])->assertRedirect();

        $expense = Expense::firstOrFail();
        $this->assertSame($case->id, $expense->case_id);
        $this->assertSame($user->id, $expense->recorded_by);
        $this->assertSame('USD', $expense->currency);
        $this->assertTrue($expense->reimbursable);

        $this->actingAs($user)->put(route('expenses.update', $expense), [
            'title' => 'Updated filing',
            'case_id' => '',
            'category' => 'filing_fees',
            'vendor' => '',
            'amount' => '75',
            'currency' => 'bdt',
            'expense_date' => '2026-11-28',
            'payment_method' => 'cash',
            'reference' => '',
            'status' => 'pending',
        ])->assertRedirect(route('expenses.show', $expense));

        $expense->refresh();
        $this->assertSame('Updated filing', $expense->title);
        $this->assertNull($expense->case_id);
        $this->assertFalse($expense->reimbursable);
        $this->assertSame('pending', $expense->status);

        $this->actingAs($user)->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_general_expense_requires_only_basic_fields(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post(route('expenses.store'), [
            'title' => 'Printer toner',
            'category' => 'office_supplies',
            'amount' => '40',
            'currency' => 'USD',
            'expense_date' => '2026-11-27',
            'payment_method' => 'card',
            'status' => 'paid',
        ])->assertRedirect();

        $expense = Expense::firstOrFail();
        $this->assertNull($expense->case_id);
        $this->assertSame('office_supplies', $expense->category);
        $this->assertSame($user->id, $expense->recorded_by);
    }

    public function test_expense_filters_and_totals_case_expenses(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();

        Expense::create([
            'case_id' => $case->id,
            'recorded_by' => $user->id,
            'title' => 'Travel',
            'category' => 'travel',
            'amount' => '60',
            'currency' => 'USD',
            'expense_date' => '2026-11-27',
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);
        Expense::create([
            'recorded_by' => $user->id,
            'title' => 'Office supplies',
            'category' => 'office_supplies',
            'amount' => '25',
            'currency' => 'USD',
            'expense_date' => '2026-11-27',
            'payment_method' => 'card',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->get(route('expenses.index', ['case_id' => $case->id]))
            ->assertOk()
            ->assertSee('Travel')
            ->assertSee('60.00');
    }

    public function test_expense_validation_requires_amount_category_and_date(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post(route('expenses.store'), [
            'title' => '',
            'currency' => 'USD',
        ])->assertSessionHasErrors(['title', 'category', 'amount', 'expense_date', 'payment_method', 'status']);
    }

    public function test_expense_pages_require_authentication(): void
    {
        $this->get(route('expenses.index'))->assertRedirect(route('login'));
        $this->get(route('expenses.create'))->assertRedirect(route('login'));
        $this->post(route('expenses.store'))->assertRedirect(route('login'));
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Expense User',
            'email' => 'expense-'.uniqid().'@example.com',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
    }

    private function createCase(): LegalCase
    {
        $client = Client::create([
            'name' => 'Expense Client',
            'phone' => '0123456789',
        ]);

        return LegalCase::create([
            'case_number' => 'EXP-'.uniqid(),
            'title' => 'Expense Case',
            'client_id' => $client->id,
            'case_type' => 'Civil',
        ]);
    }
}
