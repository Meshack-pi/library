<?php

namespace App\Livewire\Librarian;

use App\Enums\ReservationStatus;
use App\Livewire\Concerns\Flashes;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Books')]
class Books extends Component
{
    use Flashes, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $author = '';

    public string $isbn = '';

    public string $category_id = '';

    public string $shelf_location = '';

    public int|string $total_copies = 1;

    public string $newCategory = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $bookId): void
    {
        $book = Book::findOrFail($bookId);

        $this->resetForm();
        $this->editingId = $book->id;
        $this->title = $book->title;
        $this->author = $book->author;
        $this->isbn = $book->isbn;
        $this->category_id = (string) ($book->category_id ?? '');
        $this->shelf_location = (string) $book->shelf_location;
        $this->total_copies = $book->total_copies;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        Gate::authorize('books.manage');

        $book = $this->editingId ? Book::findOrFail($this->editingId) : null;
        $copiesInUse = $book ? $book->activeLoans()->count() + $book->readyReservations()->count() : 0;

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:20', Rule::unique('books', 'isbn')->ignore($this->editingId)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'shelf_location' => ['nullable', 'string', 'max:50'],
            'total_copies' => ['required', 'integer', 'min:'.max(1, $copiesInUse), 'max:10000'],
        ], [
            'total_copies.min' => $copiesInUse > 1
                ? "Total copies cannot go below {$copiesInUse}, the number currently on loan or held."
                : 'A book needs at least one copy.',
            'isbn.unique' => 'Another book (possibly a removed one) already uses this ISBN.',
        ]);

        $validated['category_id'] = $validated['category_id'] ?: null;
        $validated['shelf_location'] = $validated['shelf_location'] ?: null;

        if ($book) {
            $book->update($validated);
        } else {
            $book = Book::create($validated);
        }

        $this->flashSuccess("Saved \"{$book->title}\".");
        $this->resetForm();
    }

    public function addCategory(): void
    {
        Gate::authorize('books.manage');

        $this->validate([
            'newCategory' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')],
        ], attributes: ['newCategory' => 'category name']);

        $category = Category::create(['name' => trim($this->newCategory)]);

        $this->category_id = (string) $category->id;
        $this->newCategory = '';
    }

    public function delete(int $bookId): void
    {
        Gate::authorize('books.manage');

        $book = Book::findOrFail($bookId);

        if ($book->activeLoans()->exists()) {
            $this->flashError("\"{$book->title}\" cannot be removed while copies are on loan.");

            return;
        }

        $book->reservations()->active()->update(['status' => ReservationStatus::Cancelled]);
        $book->delete();

        $this->flashSuccess("Removed \"{$book->title}\" from the catalogue.");
    }

    public function render(): View
    {
        Gate::authorize('books.manage');

        return view('livewire.librarian.books', [
            'books' => Book::query()
                ->withAvailability()
                ->with('category')
                ->search($this->search)
                ->orderBy('title')
                ->paginate(15),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'title', 'author', 'isbn', 'category_id', 'shelf_location', 'total_copies', 'newCategory']);
        $this->resetValidation();
    }
}
