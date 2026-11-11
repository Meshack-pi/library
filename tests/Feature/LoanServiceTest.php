<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Exceptions\LoanException;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ReservationReady;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoanService $service;

    private User $librarian;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->service = new LoanService;
        $this->librarian = User::factory()->create();
        $this->member = User::factory()->create();
    }

    public function test_issuing_a_book_sets_the_due_date_from_the_loan_period(): void
    {
        Setting::put('loan_days', 10);
        $book = Book::factory()->create();

        $loan = $this->service->issue($this->member, $book, $this->librarian);

        $this->assertTrue($loan->due_at->isSameDay(today()->addDays(10)));
        $this->assertSame($this->librarian->id, $loan->issued_by);
        $this->assertSame(0, $book->availableCopies());
    }

    public function test_a_member_cannot_borrow_more_than_the_limit(): void
    {
        Setting::put('max_books', 2);
        Loan::factory()->count(2)->for($this->member)->create();

        $this->expectException(LoanException::class);
        $this->expectExceptionMessage('maximum of 2 books');

        $this->service->issue($this->member, Book::factory()->create(), $this->librarian);
    }

    public function test_returned_books_do_not_count_towards_the_limit(): void
    {
        Setting::put('max_books', 1);
        Loan::factory()->returned()->for($this->member)->create();

        $loan = $this->service->issue($this->member, Book::factory()->create(), $this->librarian);

        $this->assertModelExists($loan);
    }

    public function test_a_book_with_no_free_copy_cannot_be_issued(): void
    {
        $book = Book::factory()->create();
        Loan::factory()->for($book)->create();

        $this->expectException(LoanException::class);

        $this->service->issue($this->member, $book, $this->librarian);
    }

    public function test_a_member_cannot_borrow_two_copies_of_the_same_book(): void
    {
        $book = Book::factory()->copies(2)->create();
        $this->service->issue($this->member, $book, $this->librarian);

        $this->expectException(LoanException::class);
        $this->expectExceptionMessage('already has a copy');

        $this->service->issue($this->member, $book, $this->librarian);
    }

    public function test_a_held_copy_is_only_issued_to_the_member_it_is_held_for(): void
    {
        $book = Book::factory()->create();
        $reservation = Reservation::factory()->ready()->for($book)->for($this->member)->create();
        $someoneElse = User::factory()->create();

        try {
            $this->service->issue($someoneElse, $book, $this->librarian);
            $this->fail('The held copy was issued to another member.');
        } catch (LoanException) {
            $this->assertSame(0, $book->activeLoans()->count());
        }

        $this->service->issue($this->member, $book, $this->librarian);

        $this->assertSame(ReservationStatus::Fulfilled, $reservation->refresh()->status);
    }

    public function test_returning_on_time_creates_no_fine(): void
    {
        $loan = Loan::factory()->create(['due_at' => today()]);

        $fine = $this->service->return($loan);

        $this->assertNull($fine);
        $this->assertNotNull($loan->refresh()->returned_at);
        $this->assertSame(0, Fine::count());
    }

    public function test_returning_late_creates_a_fine_of_days_late_times_the_daily_rate(): void
    {
        Setting::put('fine_per_day', 15);
        $loan = Loan::factory()->overdueBy(4)->create();

        $fine = $this->service->return($loan);

        $this->assertSame(60, $fine->amount);
        $this->assertSame($loan->user_id, $fine->user_id);
        $this->assertFalse($fine->isPaid());
    }

    public function test_a_loan_cannot_be_returned_twice(): void
    {
        $loan = Loan::factory()->returned()->create();

        $this->expectException(LoanException::class);

        $this->service->return($loan);
    }

    public function test_returning_a_book_holds_it_for_the_member_who_reserved_first(): void
    {
        Setting::put('hold_days', 2);
        $book = Book::factory()->create();
        $loan = Loan::factory()->for($book)->create();
        $first = Reservation::factory()->for($book)->create(['created_at' => now()->subDays(2)]);
        $second = Reservation::factory()->for($book)->create(['created_at' => now()->subDay()]);

        $this->service->return($loan);

        $this->assertSame(ReservationStatus::Ready, $first->refresh()->status);
        $this->assertTrue($first->expires_at->isSameDay(today()->addDays(2)));
        $this->assertSame(ReservationStatus::Pending, $second->refresh()->status);
        $this->assertSame(0, $book->availableCopies());
        Notification::assertSentTo($first->user, ReservationReady::class);
        Notification::assertNotSentTo($second->user, ReservationReady::class);
    }

    public function test_renewing_extends_the_due_date_by_one_loan_period(): void
    {
        Setting::put('loan_days', 7);
        $loan = Loan::factory()->create(['due_at' => today()->addDays(2)]);

        $this->service->renew($loan);

        $this->assertTrue($loan->refresh()->due_at->isSameDay(today()->addDays(9)));
        $this->assertSame(1, $loan->renewals_count);
    }

    public function test_a_loan_cannot_be_renewed_when_another_member_has_reserved_the_book(): void
    {
        $loan = Loan::factory()->create();
        Reservation::factory()->for($loan->book)->create();

        $this->expectException(LoanException::class);
        $this->expectExceptionMessage('reserved');

        $this->service->renew($loan);
    }

    public function test_an_overdue_loan_cannot_be_renewed(): void
    {
        $loan = Loan::factory()->overdueBy(1)->create();

        $this->expectException(LoanException::class);
        $this->expectExceptionMessage('Overdue');

        $this->service->renew($loan);
    }

    public function test_a_loan_cannot_be_renewed_past_the_renewal_limit(): void
    {
        Setting::put('max_renewals', 1);
        $loan = Loan::factory()->create(['renewals_count' => 1]);

        $this->expectException(LoanException::class);
        $this->expectExceptionMessage('maximum of 1 times');

        $this->service->renew($loan);
    }

    public function test_an_available_book_cannot_be_reserved(): void
    {
        $this->expectException(LoanException::class);

        $this->service->reserve($this->member, Book::factory()->create());
    }

    public function test_a_member_can_reserve_a_borrowed_book_only_once(): void
    {
        $book = Book::factory()->create();
        Loan::factory()->for($book)->create();

        $reservation = $this->service->reserve($this->member, $book);
        $this->assertSame(ReservationStatus::Pending, $reservation->status);

        $this->expectException(LoanException::class);
        $this->expectExceptionMessage('already reserved');

        $this->service->reserve($this->member, $book);
    }

    public function test_cancelling_a_held_reservation_passes_the_copy_to_the_next_member(): void
    {
        $book = Book::factory()->create();
        $held = Reservation::factory()->ready()->for($book)->create();
        $next = Reservation::factory()->for($book)->create();

        $this->service->cancelReservation($held);

        $this->assertSame(ReservationStatus::Cancelled, $held->refresh()->status);
        $this->assertSame(ReservationStatus::Ready, $next->refresh()->status);
        Notification::assertSentTo($next->user, ReservationReady::class);
    }

    public function test_uncollected_holds_expire_and_free_the_copy(): void
    {
        $book = Book::factory()->create();
        $stale = Reservation::factory()->ready()->for($book)->create(['expires_at' => now()->subDay()]);
        $current = Reservation::factory()->ready()->create();

        $this->assertSame(1, $this->service->expireUncollectedHolds());

        $this->assertSame(ReservationStatus::Expired, $stale->refresh()->status);
        $this->assertSame(ReservationStatus::Ready, $current->refresh()->status);
        $this->assertSame(1, $book->availableCopies());
    }

    public function test_a_fine_payment_is_recorded_once(): void
    {
        $fine = Fine::factory()->create();

        $this->service->recordFinePayment($fine, $this->librarian);

        $this->assertTrue($fine->refresh()->isPaid());
        $this->assertSame($this->librarian->id, $fine->received_by);

        $this->expectException(LoanException::class);

        $this->service->recordFinePayment($fine, $this->librarian);
    }
}
