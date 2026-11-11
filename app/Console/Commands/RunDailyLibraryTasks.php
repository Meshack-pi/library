<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Notifications\LoanDueSoon;
use App\Notifications\LoanOverdue;
use App\Services\LoanService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('library:daily')]
#[Description('Send due and overdue reminders and release reservations that were not collected')]
class RunDailyLibraryTasks extends Command
{
    /**
     * Days between repeat overdue reminders after the first one.
     */
    private const int OVERDUE_REMINDER_INTERVAL = 7;

    public function handle(LoanService $loans): int
    {
        $expired = $loans->expireUncollectedHolds();

        $dueSoon = Loan::query()
            ->active()
            ->whereDate('due_at', today()->addDay())
            ->with(['user', 'book'])
            ->get()
            ->each(fn (Loan $loan) => $loan->user->notify(new LoanDueSoon($loan)));

        $overdue = Loan::query()
            ->overdue()
            ->with(['user', 'book'])
            ->get()
            ->filter(fn (Loan $loan): bool => $loan->daysOverdue() % self::OVERDUE_REMINDER_INTERVAL === 1)
            ->each(fn (Loan $loan) => $loan->user->notify(new LoanOverdue($loan)));

        $this->info("Due-tomorrow reminders: {$dueSoon->count()}");
        $this->info("Overdue reminders: {$overdue->count()}");
        $this->info("Reservations expired: {$expired}");

        return self::SUCCESS;
    }
}
