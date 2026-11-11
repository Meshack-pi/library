<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Exceptions\LoanException;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ReservationReady;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Check a book out to a member.
     *
     * @throws LoanException
     */
    public function issue(User $member, Book $book, User $librarian): Loan
    {
        return DB::transaction(function () use ($member, $book, $librarian): Loan {
            $maxBooks = Setting::value('max_books');

            if ($member->activeLoans()->count() >= $maxBooks) {
                throw new LoanException("{$member->name} already has the maximum of {$maxBooks} books on loan.");
            }

            if ($member->activeLoans()->where('book_id', $book->id)->exists()) {
                throw new LoanException("{$member->name} already has a copy of this book on loan.");
            }

            $reservation = $member->reservations()->active()->where('book_id', $book->id)->first();
            $hasHeldCopy = $reservation?->status === ReservationStatus::Ready;

            if (! $hasHeldCopy && $book->availableCopies() < 1) {
                throw new LoanException('No copy of this book is available. Remaining copies are on loan or held for reservations.');
            }

            $loan = Loan::create([
                'user_id' => $member->id,
                'book_id' => $book->id,
                'issued_by' => $librarian->id,
                'issued_at' => now(),
                'due_at' => today()->addDays(Setting::value('loan_days')),
            ]);

            $reservation?->update(['status' => ReservationStatus::Fulfilled]);

            return $loan;
        });
    }

    /**
     * Check a book back in. Returns the fine if the book came back late.
     *
     * @throws LoanException
     */
    public function return(Loan $loan): ?Fine
    {
        return DB::transaction(function () use ($loan): ?Fine {
            if ($loan->isReturned()) {
                throw new LoanException('This loan has already been returned.');
            }

            $loan->update(['returned_at' => now()]);

            $fine = null;
            $amount = $loan->accruedFine();

            if ($amount > 0) {
                $fine = Fine::create([
                    'loan_id' => $loan->id,
                    'user_id' => $loan->user_id,
                    'amount' => $amount,
                ]);
            }

            $this->holdCopiesForWaitingMembers($loan->book);

            return $fine;
        });
    }

    /**
     * Extend a loan by one loan period from its current due date.
     *
     * @throws LoanException
     */
    public function renew(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan): Loan {
            if ($loan->isReturned()) {
                throw new LoanException('This loan has already been returned.');
            }

            if ($loan->isOverdue()) {
                throw new LoanException('Overdue books cannot be renewed. Please return the book to the library.');
            }

            if ($loan->book->pendingReservations()->exists()) {
                throw new LoanException('This book cannot be renewed because another member has reserved it.');
            }

            $maxRenewals = Setting::value('max_renewals');

            if ($loan->renewals_count >= $maxRenewals) {
                throw new LoanException("This loan has already been renewed the maximum of {$maxRenewals} times.");
            }

            $loan->update([
                'due_at' => $loan->due_at->copy()->addDays(Setting::value('loan_days')),
                'renewals_count' => $loan->renewals_count + 1,
            ]);

            return $loan;
        });
    }

    /**
     * Join the queue for a book that has no copy available.
     *
     * @throws LoanException
     */
    public function reserve(User $member, Book $book): Reservation
    {
        return DB::transaction(function () use ($member, $book): Reservation {
            if ($book->availableCopies() > 0) {
                throw new LoanException('A copy of this book is available now, so there is no need to reserve it.');
            }

            if ($member->activeLoans()->where('book_id', $book->id)->exists()) {
                throw new LoanException('You already have this book on loan.');
            }

            if ($member->reservations()->active()->where('book_id', $book->id)->exists()) {
                throw new LoanException('You have already reserved this book.');
            }

            return Reservation::create([
                'user_id' => $member->id,
                'book_id' => $book->id,
                'status' => ReservationStatus::Pending,
            ]);
        });
    }

    /**
     * Cancel a reservation, passing any held copy to the next member waiting.
     *
     * @throws LoanException
     */
    public function cancelReservation(Reservation $reservation): void
    {
        $this->closeReservation($reservation, ReservationStatus::Cancelled);
    }

    /**
     * Expire held copies that were not collected in time. Returns how many expired.
     */
    public function expireUncollectedHolds(): int
    {
        $expired = Reservation::query()
            ->where('status', ReservationStatus::Ready)
            ->where('expires_at', '<', now())
            ->get();

        $expired->each(fn (Reservation $reservation) => $this->closeReservation($reservation, ReservationStatus::Expired));

        return $expired->count();
    }

    /**
     * Record that a fine was paid at the desk.
     *
     * @throws LoanException
     */
    public function recordFinePayment(Fine $fine, User $librarian): Fine
    {
        if ($fine->isPaid()) {
            throw new LoanException('This fine has already been paid.');
        }

        $fine->update(['paid_at' => now(), 'received_by' => $librarian->id]);

        return $fine;
    }

    /**
     * Give each free copy to the member who has waited longest, and tell them.
     */
    public function holdCopiesForWaitingMembers(Book $book): void
    {
        $free = $book->availableCopies();

        if ($free < 1) {
            return;
        }

        $book->pendingReservations()
            ->with('user')
            ->oldest()
            ->oldest('id')
            ->limit($free)
            ->get()
            ->each(function (Reservation $reservation): void {
                $reservation->update([
                    'status' => ReservationStatus::Ready,
                    'ready_at' => now(),
                    'expires_at' => today()->addDays(Setting::value('hold_days'))->endOfDay(),
                ]);

                $reservation->user->notify(new ReservationReady($reservation));
            });
    }

    /**
     * @throws LoanException
     */
    private function closeReservation(Reservation $reservation, ReservationStatus $status): void
    {
        DB::transaction(function () use ($reservation, $status): void {
            if (! $reservation->isActive()) {
                throw new LoanException('This reservation is no longer active.');
            }

            $reservation->update(['status' => $status]);

            $this->holdCopiesForWaitingMembers($reservation->book);
        });
    }
}
