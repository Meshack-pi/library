<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    public function test_a_member_can_log_in_with_their_email(): void
    {
        $user = User::factory()->create()->assignRole(Role::Member->value);

        Livewire::test(Login::class)
            ->set('login', $user->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('member.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_member_can_log_in_with_their_admission_number(): void
    {
        $user = User::factory()->create(['admission_number' => 'ADM123'])->assignRole(Role::Member->value);

        Livewire::test(Login::class)
            ->set('login', 'ADM123')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('member.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('login', $user->email)
            ->set('password', 'not-the-password')
            ->call('authenticate')
            ->assertHasErrors('login');

        $this->assertGuest();
    }

    public function test_registration_creates_a_member_account(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Jane Reader')
            ->set('email', 'jane@example.com')
            ->set('admission_number', 'ADM900')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertRedirect(route('member.dashboard'));

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(Role::Member->value));
        $this->assertFalse($user->can('books.manage'));
    }

    public function test_registration_rejects_a_duplicate_admission_number(): void
    {
        User::factory()->create(['admission_number' => 'ADM900']);

        Livewire::test(Register::class)
            ->set('name', 'Jane Reader')
            ->set('email', 'jane@example.com')
            ->set('admission_number', 'ADM900')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertHasErrors('admission_number');
    }

    public function test_a_user_can_log_out(): void
    {
        $user = User::factory()->create()->assignRole(Role::Member->value);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
