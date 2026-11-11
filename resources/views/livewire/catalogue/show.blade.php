<div>
    <a href="{{ route('catalogue.index') }}" class="text-sm text-indigo-700 hover:underline">&larr; Back to catalogue</a>

    <div class="mt-3">
        <x-flash :notice="$notice" :failure="$failure" />
    </div>

    <div class="card">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="page-title">{{ $book->title }}</h1>
                <p class="mt-1 text-slate-600">by {{ $book->author }}</p>
            </div>

            <div class="flex items-center gap-3">
                @if ($available > 0)
                    <span class="badge badge-green">Available</span>
                @else
                    <span class="badge badge-amber">Borrowed</span>
                @endif

                @if ($hasBorrowed)
                    <span class="badge badge-slate">On loan to you</span>
                @elseif ($hasReserved)
                    <span class="badge badge-slate">Reserved by you</span>
                @elseif ($available === 0)
                    @can('reservations.create')
                        <button wire:click="reserve" type="button" class="btn btn-primary">Reserve</button>
                    @endcan
                @endif
            </div>
        </div>

        <dl class="mt-6 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="stat-label">ISBN</dt>
                <dd class="mt-1">{{ $book->isbn }}</dd>
            </div>
            <div>
                <dt class="stat-label">Category</dt>
                <dd class="mt-1">{{ $book->category?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="stat-label">Shelf location</dt>
                <dd class="mt-1">{{ $book->shelf_location ?? '—' }}</dd>
            </div>
            <div>
                <dt class="stat-label">Copies available</dt>
                <dd class="mt-1">{{ $available }} of {{ $book->total_copies }}</dd>
            </div>
            <div>
                <dt class="stat-label">Members waiting</dt>
                <dd class="mt-1">{{ $waiting }}</dd>
            </div>
            @if ($available === 0 && $nextDue)
                <div>
                    <dt class="stat-label">Next copy due back</dt>
                    <dd class="mt-1">{{ \Illuminate\Support\Carbon::parse($nextDue)->format('j M Y') }}</dd>
                </div>
            @endif
        </dl>

        @if ($available > 0)
            <p class="mt-6 text-sm text-slate-600">To borrow this book, take it from shelf {{ $book->shelf_location ?? 'listed at the desk' }} to the librarian's desk.</p>
        @endif
    </div>
</div>
