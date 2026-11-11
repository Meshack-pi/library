<div>
    <h1 class="page-title">Catalogue</h1>

    <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_14rem_auto] sm:items-center">
        <div>
            <label for="search" class="sr-only">Search by title, author or ISBN</label>
            <input wire:model.live.debounce.300ms="search" id="search" type="search" class="input"
                   placeholder="Search by title, author or ISBN">
        </div>
        <div>
            <label for="category" class="sr-only">Category</label>
            <select wire:model.live="category" id="category" class="input">
                <option value="">All categories</option>
                @foreach ($categories as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input wire:model.live="availableOnly" type="checkbox" class="rounded border-slate-300">
            Available now
        </label>
    </div>

    <div class="mt-4">
        <x-flash :notice="$notice" :failure="$failure" />
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Shelf</th>
                    <th>Status</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($books as $book)
                    @php($available = $book->availableCopies())
                    <tr wire:key="book-{{ $book->id }}">
                        <td>
                            <a href="{{ route('catalogue.show', $book) }}" class="font-medium text-indigo-700 hover:underline">{{ $book->title }}</a>
                        </td>
                        <td>{{ $book->author }}</td>
                        <td>{{ $book->category?->name ?? '—' }}</td>
                        <td>{{ $book->shelf_location ?? '—' }}</td>
                        <td>
                            @if ($available > 0)
                                <span class="badge badge-green">Available ({{ $available }} of {{ $book->total_copies }})</span>
                            @else
                                <span class="badge badge-amber">Borrowed</span>
                                @if ($book->pending_reservations_count > 0)
                                    <span class="ml-1 text-xs text-slate-500">{{ $book->pending_reservations_count }} waiting</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-right">
                            @if (in_array($book->id, $borrowedBookIds))
                                <span class="badge badge-slate">On loan to you</span>
                            @elseif (in_array($book->id, $reservedBookIds))
                                <span class="badge badge-slate">Reserved by you</span>
                            @elseif ($available === 0)
                                @can('reservations.create')
                                    <button wire:click="reserve({{ $book->id }})" type="button" class="btn btn-primary btn-sm">Reserve</button>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No books match your search.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $books->links() }}</div>
</div>
