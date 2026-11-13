<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * The sidebar links each role should and should not see.
     *
     * @return array<string, array{Role, string, list<string>, list<string>}>
     */
    public static function sidebarsByRole(): array
    {
        return [
            'member' => [
                Role::Member,
                'member.dashboard',
                ['member.dashboard', 'catalogue.index'],
                ['librarian.dashboard', 'librarian.books', 'librarian.members', 'admin.reports', 'admin.settings'],
            ],
            'librarian' => [
                Role::Librarian,
                'librarian.dashboard',
                ['catalogue.index', 'librarian.dashboard', 'librarian.circulation', 'librarian.books', 'librarian.members', 'librarian.reservations', 'librarian.fines'],
                ['member.dashboard', 'admin.reports', 'admin.librarians', 'admin.settings'],
            ],
            'admin' => [
                Role::Admin,
                'admin.reports',
                ['catalogue.index', 'librarian.dashboard', 'librarian.books', 'admin.reports', 'admin.librarians', 'admin.settings'],
                ['member.dashboard'],
            ],
        ];
    }

    /**
     * @param  list<string>  $visibleRoutes
     * @param  list<string>  $hiddenRoutes
     */
    #[DataProvider('sidebarsByRole')]
    public function test_the_sidebar_only_links_to_pages_the_role_can_use(Role $role, string $homeRoute, array $visibleRoutes, array $hiddenRoutes): void
    {
        $user = User::factory()->create()->assignRole($role->value);

        $response = $this->actingAs($user)->get(route($homeRoute))->assertOk();

        foreach ($visibleRoutes as $routeName) {
            $response->assertSee('href="'.route($routeName).'"', false);
        }

        foreach ($hiddenRoutes as $routeName) {
            $response->assertDontSee('href="'.route($routeName).'"', false);
        }
    }

    public function test_the_current_page_is_marked_as_active_in_the_sidebar(): void
    {
        $librarian = User::factory()->create()->assignRole(Role::Librarian->value);

        $this->actingAs($librarian)->get(route('librarian.books'))
            ->assertOk()
            ->assertSeeInOrder(['href="'.route('librarian.books').'"', 'aria-current="page"', 'nav-link-active'], false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSeeText('aria-current="page" class="nav-link"');

        $this->assertSame(1, substr_count(
            $this->actingAs($librarian)->get(route('librarian.books'))->getContent(),
            'aria-current="page"',
        ));
    }

    public function test_the_sidebar_shows_who_is_signed_in_and_a_log_out_button(): void
    {
        $member = User::factory()->create(['name' => 'Wanjiru Kamau'])->assignRole(Role::Member->value);

        $this->actingAs($member)->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Sidebar"', false)
            ->assertSeeText('Wanjiru Kamau')
            ->assertSeeText('member')
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSeeText('Log out');
    }

    public function test_the_sidebar_can_be_opened_from_a_menu_button_on_small_screens(): void
    {
        $member = User::factory()->create()->assignRole(Role::Member->value);

        $this->actingAs($member)->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('aria-controls="sidebar"', false)
            ->assertSee('@click="sidebarOpen = true"', false)
            ->assertSeeText('Close menu');
    }

    public function test_guests_see_the_styled_login_screen_without_the_sidebar(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Welcome back')
            ->assertSee('btn btn-primary btn-block', false)
            ->assertDontSee('aria-label="Sidebar"', false);
    }
}
