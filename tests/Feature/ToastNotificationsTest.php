<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Admin\Settings;
use App\Livewire\Member\Dashboard as MemberDashboard;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToastNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_no_toast_is_shown_before_any_action(): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);

        Livewire::actingAs($admin)->test(Settings::class)
            ->assertDontSeeHtml('class="toast');
    }

    public function test_a_successful_action_shows_a_success_toast(): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);

        Livewire::actingAs($admin)->test(Settings::class)
            ->call('save')
            ->assertSet('flashVersion', 1)
            ->assertSeeHtml('toast toast-success')
            ->assertSeeHtml('role="status"')
            ->assertSeeHtml('wire:key="toast-1"')
            ->assertSee('Library rules saved.')
            ->assertSee('Dismiss notification');
    }

    public function test_repeating_an_action_gives_a_fresh_toast_each_time(): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);

        Livewire::actingAs($admin)->test(Settings::class)
            ->call('save')
            ->call('save')
            ->assertSet('flashVersion', 2)
            ->assertSeeHtml('wire:key="toast-2"')
            ->assertDontSeeHtml('wire:key="toast-1"');
    }

    public function test_a_failed_action_shows_an_error_toast_and_replaces_the_success_one(): void
    {
        $member = User::factory()->create()->assignRole(Role::Member->value);
        $loan = Loan::factory()->for($member)->for(Book::factory()->create())->create();
        $loan->update(['renewals_count' => 99]);

        Livewire::actingAs($member)->test(MemberDashboard::class)
            ->set('notice', 'Earlier success')
            ->call('renew', $loan->id)
            ->assertSet('notice', null)
            ->assertSeeHtml('toast toast-error')
            ->assertSeeHtml('role="alert"')
            ->assertDontSee('Earlier success');
    }
}
