<?php

namespace App\Livewire\Librarian;

use App\Exceptions\LoanException;
use App\Livewire\Concerns\Flashes;
use App\Models\Reservation;
use App\Services\LoanService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reservations')]
class Reservations extends Component
{
    use Flashes;

    public function cancel(int $reservationId): void
    {
        Gate::authorize('reservations.manage');

        $reservation = Reservation::with(['book', 'user'])->findOrFail($reservationId);

        try {
            app(LoanService::class)->cancelReservation($reservation);
            $this->flashSuccess("Cancelled {$reservation->user->name}'s reservation for \"{$reservation->book->title}\".");
        } catch (LoanException $exception) {
            $this->flashError($exception->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('reservations.manage');

        return view('livewire.librarian.reservations', [
            'reservations' => Reservation::query()
                ->active()
                ->with(['book', 'user'])
                ->orderByDesc('status')
                ->oldest()
                ->get(),
        ]);
    }
}
