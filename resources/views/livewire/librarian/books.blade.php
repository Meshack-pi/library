<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="page-title">Books</h1>
        <button wire:click="create" type="button" class="btn btn-primary"><x-icon name="plus" /> Add book</button>
    </div>

    <div class="mt-4">
        <x-flash :notice="$notice" :failure="$failure" :version="$flashVersion" />
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card mb-6">
            <h2 class="card-title">{{ $editingId ? 'Edit book' : 'Add a book' }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="title" class="label">Title</label>
                    <input wire:model="title" id="title" type="text" class="input" required>
                    @error('title') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="author" class="label">Author</label>
                    <input wire:model="author" id="author" type="text" class="input" required>
                    @error('author') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="isbn" class="label">ISBN</label>
                    <input wire:model="isbn" id="isbn" type="text" class="input" required>
                    @error('isbn') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="shelf_location" class="label">Shelf location</label>
                    <input wire:model="shelf_location" id="shelf_location" type="text" class="input" placeholder="e.g. CS-02">
                    @error('shelf_location') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category_id" class="label">Category</label>
                    <select wire:model="category_id" id="category_id" class="input">
                        <option value="">No category</option>
                        @foreach ($categories as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="field-error">{{ $message }}</p> @enderror

                    <div class="mt-2 flex gap-2">
                        <label for="newCategory" class="sr-only">New category name</label>
                        <input wire:model="newCategory" wire:keydown.enter.prevent="addCategory" id="newCategory" type="text" class="input" placeholder="Or add a new category">
                        <button wire:click="addCategory" type="button" class="btn btn-secondary"><x-icon name="tag" /> Add</button>
                    </div>
                    @error('newCategory') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="total_copies" class="label">Total copies</label>
                    <input wire:model="total_copies" id="total_copies" type="number" min="1" class="input" required>
                    @error('total_copies') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex gap-2">
                <button type="submit" class="btn btn-dark"><x-icon name="check" /> Save book</button>
                <button wire:click="cancel" type="button" class="btn btn-secondary"><x-icon name="x" /> Cancel</button>
            </div>
        </form>
    @endif

    <div class="mb-3 max-w-md">
        <label for="search" class="sr-only">Search books</label>
        <input wire:model.live.debounce.300ms="search" id="search" type="search" class="input" placeholder="Search by title, author or ISBN">
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Shelf</th>
                    <th>Available</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($books as $book)
                    <tr wire:key="book-{{ $book->id }}">
                        <td class="text-ink-500">{{ $book->id }}</td>
                        <td class="font-medium">{{ $book->title }}</td>
                        <td>{{ $book->author }}</td>
                        <td>{{ $book->isbn }}</td>
                        <td>{{ $book->category?->name ?? '—' }}</td>
                        <td>{{ $book->shelf_location ?? '—' }}</td>
                        <td>{{ $book->availableCopies() }} of {{ $book->total_copies }}</td>
                        <td class="text-right whitespace-nowrap">
                            <button wire:click="edit({{ $book->id }})" type="button" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="size-4" /> Edit</button>
                            <button wire:click="delete({{ $book->id }})" wire:confirm="Remove &quot;{{ $book->title }}&quot; from the catalogue?"
                                    type="button" class="btn btn-danger btn-sm"><x-icon name="trash" class="size-4" /> Remove</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty">No books found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $books->links() }}</div>
</div>
