<?php

namespace App\Livewire\Member;

use App\Exceptions\LoanException;
use App\Livewire\Concerns\Flashes;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('My library')]
class Dashboard extends Component
{
    use Flashes;

    public function renew(int $loanId): void
    {
        Gate::authorize('loans.renew-own');

        $loan = $this->member()->activeLoans()->with('book')->findOrFail($loanId);

        try {
            app(LoanService::class)->renew($loan);
            $this->flashSuccess("Renewed \"{$loan->book->title}\". It is now due on {$loan->due_at->format('j M Y')}.");
        } catch (LoanException $exception) {
            $this->flashError($exception->getMessage());
        }
    }

    public function cancelReservation(int $reservationId): void
    {
        $reservation = $this->member()->reservations()->with('book')->findOrFail($reservationId);

        try {
            app(LoanService::class)->cancelReservation($reservation);
            $this->flashSuccess("Cancelled your reservation for \"{$reservation->book->title}\".");
        } catch (LoanException $exception) {
            $this->flashError($exception->getMessage());
        }
    }

    public function markNotificationsRead(): void
    {
        $this->member()->unreadNotifications->markAsRead();
    }

    public function render(): View
    {
        $member = $this->member();

        $loans = $member->activeLoans()->with('book')->orderBy('due_at')->get();
        $unpaidFines = $member->unpaidFines()->with('loan.book')->latest()->get();
        $accruing = $loans->sum(fn (Loan $loan): int => $loan->accruedFine());

        return view('livewire.member.dashboard', [
            'loans' => $loans,
            'reservations' => $member->reservations()->active()->with('book')->oldest()->get(),
            'unpaidFines' => $unpaidFines,
            'accruing' => $accruing,
            'totalOwed' => $unpaidFines->sum('amount') + $accruing,
            'notifications' => $member->unreadNotifications()->latest()->limit(10)->get(),
        ]);
    }

    private function member(): User
    {
        return Auth::user();
    }
}
