<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Livewire\Admin\Librarians;
use App\Livewire\Admin\Settings;
use App\Livewire\Catalogue\Index as Catalogue;
use App\Livewire\Librarian\Books;
use App\Livewire\Librarian\Fines;
use App\Livewire\Librarian\IssueReturn;
use App\Livewire\Librarian\Members;
use App\Livewire\Member\Dashboard as MemberDashboard;
use App\Models\Book;
use App\Models\Category;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ScreenActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $librarian;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create()->assignRole(Role::Admin->value);
        $this->librarian = User::factory()->create()->assignRole(Role::Librarian->value);
        $this->member = User::factory()->create(['admission_number' => 'ADM500'])->assignRole(Role::Member->value);
    }

    public function test_the_catalogue_search_and_filters_narrow_the_list(): void
    {
        $fiction = Category::factory()->create(['name' => 'Fiction']);
        Book::factory()->for($fiction)->create(['title' => 'Things Fall Apart', 'author' => 'Chinua Achebe']);
        $borrowed = Book::factory()->create(['title' => 'Clean Code', 'author' => 'Robert Martin']);
        Loan::factory()->for($borrowed)->create();

        Livewire::actingAs($this->member)->test(Catalogue::class)
            ->assertSee(['Things Fall Apart', 'Clean Code'])
            ->set('search', 'achebe')
            ->assertSee('Things Fall Apart')
            ->assertDontSee('Clean Code')
            ->set('search', '')
            ->set('category', (string) $fiction->id)
            ->assertDontSee('Clean Code')
            ->set('category', '')
            ->set('availableOnly', true)
            ->assertSee('Things Fall Apart')
            ->assertDontSee('Clean Code');
    }

    public function test_a_member_can_reserve_a_borrowed_book_from_the_catalogue(): void
    {
        $book = Book::factory()->create();
        Loan::factory()->for($book)->create();

        Livewire::actingAs($this->member)->test(Catalogue::class)
            ->call('reserve', $book->id)
            ->assertSee('Reserved by you');

        $this->assertSame(1, $this->member->reservations()->where('book_id', $book->id)->count());
    }

    public function test_a_librarian_can_add_edit_and_remove_a_book(): void
    {
        $category = Category::factory()->create();

        $screen = Livewire::actingAs($this->librarian)->test(Books::class)
            ->call('create')
            ->set('title', 'Software Engineering')
            ->set('author', 'Ian Sommerville')
            ->set('isbn', '9780133943030')
            ->set('category_id', (string) $category->id)
            ->set('total_copies', 3)
            ->call('save')
            ->assertHasNoErrors();

        $book = Book::where('isbn', '9780133943030')->firstOrFail();
        $this->assertSame(3, $book->total_copies);

        $screen->call('edit', $book->id)
            ->assertSet('title', 'Software Engineering')
            ->set('total_copies', 5)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(5, $book->refresh()->total_copies);

        $screen->call('delete', $book->id);

        $this->assertSoftDeleted($book);
    }

    public function test_a_book_needs_a_unique_isbn_and_cannot_be_removed_while_on_loan(): void
    {
        $book = Book::factory()->create(['isbn' => '111']);
        Loan::factory()->for($book)->create();

        Livewire::actingAs($this->librarian)->test(Books::class)
            ->call('create')
            ->set('title', 'Copy')
            ->set('author', 'Someone')
            ->set('isbn', '111')
            ->call('save')
            ->assertHasErrors('isbn')
            ->call('delete', $book->id)
            ->assertSee('cannot be removed');

        $this->assertNotSoftDeleted($book);
    }

    public function test_a_member_cannot_run_book_management_actions(): void
    {
        $book = Book::factory()->create();

        Livewire::actingAs($this->member)->test(Books::class)->assertForbidden();

        $this->assertNotSoftDeleted($book);
    }

    public function test_a_librarian_can_issue_a_book_by_admission_number_and_isbn(): void
    {
        $book = Book::factory()->create(['isbn' => '9780132350884']);

        Livewire::actingAs($this->librarian)->test(IssueReturn::class)
            ->set('memberIdentifier', 'ADM500')
            ->set('bookIdentifier', '9780132350884')
            ->call('issue')
            ->assertHasNoErrors()
            ->assertSee('Due back on '.today()->addDays(14)->format('j M Y'));

        $loan = $this->member->activeLoans()->firstOrFail();
        $this->assertSame($book->id, $loan->book_id);
        $this->assertSame($this->librarian->id, $loan->issued_by);
    }

    public function test_issuing_reports_unknown_members_and_broken_rules(): void
    {
        Setting::put('max_books', 1);
        Loan::factory()->for($this->member)->create();
        $book = Book::factory()->create();

        Livewire::actingAs($this->librarian)->test(IssueReturn::class)
            ->set('memberIdentifier', 'NOBODY')
            ->set('bookIdentifier', $book->isbn)
            ->call('issue')
            ->assertHasErrors('memberIdentifier')
            ->set('memberIdentifier', 'ADM500')
            ->call('issue')
            ->assertSee('maximum of 1 books');

        $this->assertSame(1, $this->member->activeLoans()->count());
    }

    public function test_returning_a_late_book_shows_the_fine(): void
    {
        $loan = Loan::factory()->for($this->member)->overdueBy(3)->create();

        Livewire::actingAs($this->librarian)->test(IssueReturn::class)
            ->set('returnSearch', 'ADM500')
            ->assertSee('3 days late')
            ->call('returnLoan', $loan->id)
            ->assertSee('fine of KES 30 added');

        $this->assertNotNull($loan->refresh()->returned_at);
        $this->assertSame(30, $this->member->unpaidFines()->sum('amount'));
    }

    public function test_a_librarian_can_record_a_fine_payment(): void
    {
        $fine = Fine::factory()->create();

        Livewire::actingAs($this->librarian)->test(Fines::class)
            ->call('recordPayment', $fine->id)
            ->assertSee('Recorded payment');

        $this->assertTrue($fine->refresh()->isPaid());
        $this->assertSame($this->librarian->id, $fine->received_by);
    }

    public function test_a_librarian_can_register_and_update_a_member_but_not_staff(): void
    {
        $screen = Livewire::actingAs($this->librarian)->test(Members::class)
            ->call('create')
            ->set('name', 'New Student')
            ->set('email', 'student@example.com')
            ->set('admission_number', 'ADM900')
            ->set('password', 'secret-password')
            ->call('save')
            ->assertHasNoErrors();

        $created = User::where('email', 'student@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole(Role::Member->value));

        $screen->call('edit', $created->id)
            ->set('name', 'Renamed Student')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Renamed Student', $created->refresh()->name);

        $screen->call('edit', $this->admin->id)->assertStatus(404);
    }

    public function test_a_member_can_renew_their_own_loan_but_not_someone_elses(): void
    {
        $own = Loan::factory()->for($this->member)->create(['due_at' => today()->addDay()]);
        $other = Loan::factory()->create();

        $screen = Livewire::actingAs($this->member)->test(MemberDashboard::class)
            ->call('renew', $own->id)
            ->assertSee('Renewed');

        $this->assertSame(1, $own->refresh()->renewals_count);

        $screen->call('renew', $other->id)->assertStatus(404);

        $this->assertSame(0, $other->refresh()->renewals_count);
    }

    public function test_a_member_sees_why_a_renewal_was_refused_and_can_cancel_a_reservation(): void
    {
        $loan = Loan::factory()->for($this->member)->create();
        Reservation::factory()->for($loan->book)->create();
        $reservation = Reservation::factory()->for($this->member)->create();

        Livewire::actingAs($this->member)->test(MemberDashboard::class)
            ->call('renew', $loan->id)
            ->assertSee('another member has reserved it')
            ->call('cancelReservation', $reservation->id);

        $this->assertSame(0, $loan->refresh()->renewals_count);
        $this->assertSame(ReservationStatus::Cancelled, $reservation->refresh()->status);
    }

    public function test_an_admin_can_change_the_library_rules(): void
    {
        Livewire::actingAs($this->admin)->test(Settings::class)
            ->assertSet('values.loan_days', 14)
            ->set('values.loan_days', 21)
            ->set('values.fine_per_day', 20)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(21, Setting::value('loan_days'));
        $this->assertSame(20, Setting::value('fine_per_day'));

        Livewire::actingAs($this->admin)->test(Settings::class)
            ->set('values.max_books', 0)
            ->call('save')
            ->assertHasErrors('values.max_books');

        Livewire::actingAs($this->librarian)->test(Settings::class)->assertForbidden();
    }

    public function test_an_admin_can_add_a_librarian_and_remove_their_access(): void
    {
        $screen = Livewire::actingAs($this->admin)->test(Librarians::class)
            ->call('create')
            ->set('name', 'New Librarian')
            ->set('email', 'desk@example.com')
            ->set('password', 'secret-password')
            ->call('save')
            ->assertHasNoErrors();

        $created = User::where('email', 'desk@example.com')->firstOrFail();
        $this->assertTrue($created->can('loans.issue'));

        $screen->call('revoke', $created->id);

        $created->refresh()->unsetRelations();
        $this->assertFalse($created->hasRole(Role::Librarian->value));
        $this->assertTrue($created->hasRole(Role::Member->value));
    }
}
