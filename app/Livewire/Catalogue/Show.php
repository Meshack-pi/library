<?php

namespace App\Livewire\Catalogue;

use App\Exceptions\LoanException;
use App\Livewire\Concerns\Flashes;
use App\Models\Book;
use App\Services\LoanService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    use Flashes;

    public Book $book;

    public function mount(Book $book): void
    {
        Gate::authorize('catalogue.view');

        $this->book = $book;
    }

    public function reserve(): void
    {
        Gate::authorize('reservations.create');

        try {
            app(LoanService::class)->reserve(Auth::user(), $this->book);
            $this->flashSuccess('Reserved. We will notify you when a copy is ready.');
        } catch (LoanException $exception) {
            $this->flashError($exception->getMessage());
        }
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.catalogue.show', [
            'available' => $this->book->availableCopies(),
            'waiting' => $this->book->pendingReservations()->count(),
            'nextDue' => $this->book->activeLoans()->min('due_at'),
            'hasReserved' => $user->reservations()->active()->where('book_id', $this->book->id)->exists(),
            'hasBorrowed' => $user->activeLoans()->where('book_id', $this->book->id)->exists(),
        ])->title($this->book->title);
    }
}
