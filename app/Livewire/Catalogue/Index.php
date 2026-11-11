<?php

namespace App\Livewire\Catalogue;

use App\Exceptions\LoanException;
use App\Livewire\Concerns\Flashes;
use App\Models\Book;
use App\Models\Category;
use App\Services\LoanService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Catalogue')]
class Index extends Component
{
    use Flashes, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url(as: 'available')]
    public bool $availableOnly = false;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'availableOnly'], true)) {
            $this->resetPage();
        }
    }

    public function reserve(int $bookId): void
    {
        Gate::authorize('reservations.create');

        try {
            $book = Book::findOrFail($bookId);
            app(LoanService::class)->reserve(Auth::user(), $book);
            $this->flashSuccess("You have reserved \"{$book->title}\". We will notify you when a copy is ready.");
        } catch (LoanException $exception) {
            $this->flashError($exception->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('catalogue.view');

        $user = Auth::user();

        return view('livewire.catalogue.index', [
            'books' => Book::query()
                ->withAvailability()
                ->with('category')
                ->search($this->search)
                ->when($this->category !== '', fn ($query) => $query->where('category_id', (int) $this->category))
                ->when($this->availableOnly, fn ($query) => $query->available())
                ->orderBy('title')
                ->paginate(12),
            'categories' => Category::orderBy('name')->get(),
            'reservedBookIds' => $user->reservations()->active()->pluck('book_id')->all(),
            'borrowedBookIds' => $user->activeLoans()->pluck('book_id')->all(),
        ]);
    }
}
