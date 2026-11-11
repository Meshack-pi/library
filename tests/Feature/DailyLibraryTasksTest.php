<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Loan;
use App\Models\Reservation;
use App\Notifications\LoanDueSoon;
use App\Notifications\LoanOverdue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DailyLibraryTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reminds_members_whose_books_are_due_tomorrow(): void
    {
        Notification::fake();
        $dueTomorrow = Loan::factory()->create(['due_at' => today()->addDay()]);
        $dueLater = Loan::factory()->create(['due_at' => today()->addDays(5)]);
        $alreadyBack = Loan::factory()->returned()->create(['due_at' => today()->addDay()]);

        $this->artisan('library:daily')->assertSuccessful();

        Notification::assertSentTo($dueTomorrow->user, LoanDueSoon::class);
        Notification::assertNotSentTo($dueLater->user, LoanDueSoon::class);
        Notification::assertNotSentTo($alreadyBack->user, LoanDueSoon::class);
    }

    public function test_it_reminds_overdue_members_on_the_first_day_and_then_weekly(): void
    {
        Notification::fake();
        $oneDay = Loan::factory()->overdueBy(1)->create();
        $threeDays = Loan::factory()->overdueBy(3)->create();
        $eightDays = Loan::factory()->overdueBy(8)->create();

        $this->artisan('library:daily')->assertSuccessful();

        Notification::assertSentTo($oneDay->user, LoanOverdue::class);
        Notification::assertNotSentTo($threeDays->user, LoanOverdue::class);
        Notification::assertSentTo($eightDays->user, LoanOverdue::class);
    }

    public function test_it_expires_reservations_that_were_not_collected(): void
    {
        Notification::fake();
        $stale = Reservation::factory()->ready()->create(['expires_at' => now()->subHour()]);

        $this->artisan('library:daily')->assertSuccessful();

        $this->assertSame(ReservationStatus::Expired, $stale->refresh()->status);
    }

    public function test_reminders_are_stored_for_the_member_dashboard(): void
    {
        $loan = Loan::factory()->overdueBy(1)->create();

        $this->artisan('library:daily')->assertSuccessful();

        $notification = $loan->user->notifications()->firstOrFail();
        $this->assertStringContainsString('1 day overdue', $notification->data['message']);
    }
}
