<?php

namespace App\Livewire\Librarian;

use App\Exceptions\LoanException;
use App\Livewire\Concerns\Flashes;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Issue / Return')]
class IssueReturn extends Component
{
    use Flashes;

    #[Url(as: 'member')]
    public string $memberIdentifier = '';

    #[Url(as: 'book')]
    public string $bookIdentifier = '';

    public string $returnSearch = '';

    public function issue(): void
    {
        Gate::authorize('loans.issue');

        $this->validate([
            'memberIdentifier' => ['required', 'string'],
            'bookIdentifier' => ['required', 'string'],
        ], attributes: ['memberIdentifier' => 'member', 'bookIdentifier' => 'book']);

        $member = User::findByIdentifier($this->memberIdentifier);
        $book = Book::findByIdentifier($this->bookIdentifier);

        if (! $member) {
            $this->addError('memberIdentifier', 'No member has this admission number or email.');
        }

        if (! $book) {
            $this->addError('bookIdentifier', 'No book has this ISBN or book ID.');
        }

        if (! $member || ! $book) {
            return;
        }

        try {
            $loan = app(LoanService::class)->issue($member, $book, Auth::user());
        } catch (LoanException $exception) {
            $this->flashError($exception->getMessage());

            return;
        }

        $this->flashSuccess("Issued \"{$book->title}\" to {$member->name}. Due back on {$loan->due_at->format('j M Y')}.");
        $this->reset(['memberIdentifier', 'bookIdentifier']);
    }

    public function returnLoan(int $loanId): void
    {
        Gate::authorize('loans.return');

        $loan = Loan::with(['book', 'user'])->findOrFail($loanId);

        try {
            $fine = app(LoanService::class)->return($loan);
        } catch (LoanException $exception) {
            $this->flashError($exception->getMessage());

            return;
        }

        $message = "Returned \"{$loan->book->title}\" from {$loan->user->name}.";

        $this->flashSuccess($fine
            ? "{$message} It was {$loan->daysOverdue()} days late: fine of KES ".number_format($fine->amount).' added.'
            : "{$message} No fine.");
    }

    public function render(): View
    {
        Gate::authorize('loans.issue');

        $member = User::findByIdentifier($this->memberIdentifier);
        $book = Book::findByIdentifier($this->bookIdentifier);

        return view('livewire.librarian.issue-return', [
            'member' => $member,
            'memberLoanCount' => $member?->activeLoans()->count(),
            'maxBooks' => Setting::value('max_books'),
            'book' => $book,
            'bookAvailable' => $book?->availableCopies(),
            'loansToReturn' => $this->loansToReturn(),
        ]);
    }

    /**
     * Open loans matching the member or book typed into the return search.
     *
     * @return Collection<int, Loan>
     */
    private function loansToReturn(): Collection
    {
        $term = trim($this->returnSearch);

        if ($term === '') {
            return collect();
        }

        return Loan::query()
            ->active()
            ->where(function (Builder $query) use ($term) {
                $query
                    ->whereHas('user', fn (Builder $user) => $user
                        ->where('admission_number', $term)
                        ->orWhere('email', strtolower($term))
                        ->orWhere('name', 'like', "%{$term}%"))
                    ->orWhereHas('book', fn (Builder $book) => $book
                        ->withTrashed()
                        ->where('isbn', $term)
                        ->orWhere('title', 'like', "%{$term}%"));
            })
            ->with(['user', 'book'])
            ->orderBy('due_at')
            ->limit(25)
            ->get();
    }
}
