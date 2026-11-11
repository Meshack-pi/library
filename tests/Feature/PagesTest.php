<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Every page with the roles allowed to open it.
     *
     * @return array<string, array{string, list<Role>}>
     */
    public static function pages(): array
    {
        $staff = [Role::Librarian, Role::Admin];
        $everyone = [Role::Member, Role::Librarian, Role::Admin];

        return [
            'member dashboard' => ['member.dashboard', [Role::Member]],
            'catalogue' => ['catalogue.index', $everyone],
            'librarian desk' => ['librarian.dashboard', $staff],
            'issue and return' => ['librarian.circulation', $staff],
            'books' => ['librarian.books', $staff],
            'members' => ['librarian.members', $staff],
            'reservations' => ['librarian.reservations', $staff],
            'fines' => ['librarian.fines', $staff],
            'reports' => ['admin.reports', [Role::Admin]],
            'librarians' => ['admin.librarians', [Role::Admin]],
            'settings' => ['admin.settings', [Role::Admin]],
        ];
    }

    /**
     * @param  list<Role>  $allowed
     */
    #[DataProvider('pages')]
    public function test_each_page_opens_only_for_the_roles_allowed_to_use_it(string $routeName, array $allowed): void
    {
        $this->seedActivity();

        foreach (Role::cases() as $role) {
            $user = User::factory()->create()->assignRole($role->value);
            $response = $this->actingAs($user)->get(route($routeName));

            in_array($role, $allowed, true) ? $response->assertOk() : $response->assertForbidden();
        }
    }

    public function test_the_book_details_page_shows_availability(): void
    {
        $member = User::factory()->create()->assignRole(Role::Member->value);
        $book = Book::factory()->copies(2)->create(['title' => 'Clean Code']);
        Loan::factory()->for($book)->create();

        $this->actingAs($member)->get(route('catalogue.show', $book))
            ->assertOk()
            ->assertSee('Clean Code')
            ->assertSee('1 of 2');
    }

    public function test_the_member_dashboard_shows_only_the_members_own_records(): void
    {
        $member = User::factory()->create()->assignRole(Role::Member->value);
        Loan::factory()->for($member)->for(Book::factory()->create(['title' => 'My Borrowed Book']))->overdueBy(3)->create();
        Loan::factory()->for(Book::factory()->create(['title' => 'Somebody Else Book']))->create();

        $this->actingAs($member)->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('My Borrowed Book')
            ->assertSee('3 days overdue')
            ->assertSee('KES 30')
            ->assertDontSee('Somebody Else Book');
    }

    /**
     * Give every list on every page at least one row to render.
     */
    private function seedActivity(): void
    {
        $member = User::factory()->create(['admission_number' => 'ADM777'])->assignRole(Role::Member->value);

        Loan::factory()->for($member)->create(['due_at' => today()]);
        Loan::factory()->for($member)->overdueBy(5)->create();
        Reservation::factory()->for($member)->create();
        Reservation::factory()->for($member)->ready()->create();
        Fine::factory()->create();
        Fine::factory()->paid()->create();
    }
}
