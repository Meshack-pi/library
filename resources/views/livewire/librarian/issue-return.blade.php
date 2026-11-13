<div>
    <h1 class="page-title">Issue / Return</h1>

    <div class="mt-4">
        <x-flash :notice="$notice" :failure="$failure" :version="$flashVersion" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="issue" class="card">
            <h2 class="card-title">Issue a book</h2>

            <div class="space-y-4">
                <div>
                    <label for="memberIdentifier" class="label">Member admission number or email</label>
                    <input wire:model.live.debounce.400ms="memberIdentifier" id="memberIdentifier" type="text" class="input" autocomplete="off" required>
                    @error('memberIdentifier') <p class="field-error">{{ $message }}</p> @enderror
                    @if ($member)
                        <p class="mt-1 text-sm text-ink-600">
                            {{ $member->name }} · {{ $memberLoanCount }} of {{ $maxBooks }} books on loan
                            @if ($memberLoanCount >= $maxBooks) <span class="badge badge-red">At limit</span> @endif
                        </p>
                    @endif
                </div>

                <div>
                    <label for="bookIdentifier" class="label">Book ISBN or book ID</label>
                    <input wire:model.live.debounce.400ms="bookIdentifier" id="bookIdentifier" type="text" class="input" autocomplete="off" required>
                    @error('bookIdentifier') <p class="field-error">{{ $message }}</p> @enderror
                    @if ($book)
                        <p class="mt-1 text-sm text-ink-600">
                            {{ $book->title }} · {{ $bookAvailable }} of {{ $book->total_copies }} available
                        </p>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="issue"><x-icon name="arrow-up-tray" /> Issue book</button>
            </div>
        </form>

        <div class="card" id="return">
            <h2 class="card-title">Return a book</h2>

            <label for="returnSearch" class="label">Find the loan by member (name, admission number, email) or book (title, ISBN)</label>
            <input wire:model.live.debounce.400ms="returnSearch" id="returnSearch" type="search" class="input" autocomplete="off">

            @if (trim($returnSearch) !== '')
                <ul class="mt-4 divide-y divide-ink-100">
                    @forelse ($loansToReturn as $loan)
                        <li wire:key="loan-{{ $loan->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                            <div>
                                <p class="font-medium">{{ $loan->book->title }}</p>
                                <p class="text-ink-600">{{ $loan->user->name }} · due {{ $loan->due_at->format('j M Y') }}</p>
                                @if ($loan->isOverdue())
                                    <p class="mt-1">
                                        <span class="badge badge-red">{{ $loan->daysOverdue() }} days late</span>
                                        <span class="ml-1 text-rose-700">Fine: KES {{ number_format($loan->accruedFine()) }}</span>
                                    </p>
                                @endif
                            </div>
                            <button wire:click="returnLoan({{ $loan->id }})" type="button" class="btn btn-dark"><x-icon name="arrow-down-tray" /> Return</button>
                        </li>
                    @empty
                        <li class="empty">No open loans match.</li>
                    @endforelse
                </ul>
            @endif
        </div>
    </div>
</div>
