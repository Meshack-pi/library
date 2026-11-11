<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * @return array<string, array{Role, string}>
     */
    public static function roleHomeRoutes(): array
    {
        return [
            'admin' => [Role::Admin, 'admin.reports'],
            'librarian' => [Role::Librarian, 'librarian.dashboard'],
            'member' => [Role::Member, 'member.dashboard'],
        ];
    }

    #[DataProvider('roleHomeRoutes')]
    public function test_the_dashboard_sends_each_role_to_its_own_home(Role $role, string $routeName): void
    {
        $user = User::factory()->create()->assignRole($role->value);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route($routeName));
        $this->actingAs($user)->get(route($routeName))->assertOk();
    }
}
