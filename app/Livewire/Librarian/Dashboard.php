<?php

namespace App\Livewire\Librarian;

use App\Enums\ReservationStatus;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Librarian desk')]
class Dashboard extends Component
{
    public function render(): View
    {
        Gate::authorize('loans.issue');

        return view('livewire.librarian.dashboard', [
            'onLoan' => Loan::query()->active()->count(),
            'unpaidFines' => (int) Fine::query()->unpaid()->sum('amount'),
            'dueToday' => Loan::query()->active()->whereDate('due_at', today())->with(['user', 'book'])->get(),
            'overdue' => Loan::query()->overdue()->with(['user', 'book'])->orderBy('due_at')->get(),
            'holds' => Reservation::query()
                ->where('status', ReservationStatus::Ready)
                ->with(['user', 'book'])
                ->orderBy('expires_at')
                ->get(),
        ]);
    }
}
