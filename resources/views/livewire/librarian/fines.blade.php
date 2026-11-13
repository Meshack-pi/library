<div>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="page-title">Fines</h1>
        <p class="text-sm text-ink-600">Total unpaid: <span class="font-semibold text-ink-900">KES {{ number_format($totalUnpaid) }}</span></p>
    </div>

    <div class="mt-4">
        <x-flash :notice="$notice" :failure="$failure" :version="$flashVersion" />
    </div>

    <div class="mb-3 grid gap-3 sm:grid-cols-[1fr_12rem]">
        <div>
            <label for="search" class="sr-only">Search by member</label>
            <input wire:model.live.debounce.300ms="search" id="search" type="search" class="input" placeholder="Search by member name, email or admission number">
        </div>
        <div>
            <label for="status" class="sr-only">Status</label>
            <select wire:model.live="status" id="status" class="input">
                <option value="unpaid">Unpaid</option>
                <option value="paid">Paid</option>
                <option value="all">All fines</option>
            </select>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Book</th>
                    <th>Returned</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fines as $fine)
                    <tr wire:key="fine-{{ $fine->id }}">
                        <td>{{ $fine->user->name }} <span class="text-ink-500">{{ $fine->user->admission_number }}</span></td>
                        <td>{{ $fine->loan->book->title }}</td>
                        <td>{{ $fine->loan->returned_at?->format('j M Y') }} <span class="text-ink-500">({{ $fine->loan->daysOverdue() }} days late)</span></td>
                        <td>KES {{ number_format($fine->amount) }}</td>
                        <td>
                            @if ($fine->isPaid())
                                <span class="badge badge-green">Paid {{ $fine->paid_at->format('j M Y') }}</span>
                                @if ($fine->receiver)
                                    <span class="ml-1 text-xs text-ink-500">to {{ $fine->receiver->name }}</span>
                                @endif
                            @else
                                <span class="badge badge-red">Unpaid</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @unless ($fine->isPaid())
                                <button wire:click="recordPayment({{ $fine->id }})"
                                        wire:confirm="Record payment of KES {{ number_format($fine->amount) }} from {{ $fine->user->name }}?"
                                        type="button" class="btn btn-dark btn-sm"><x-icon name="banknotes" class="size-4" /> Record payment</button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No fines to show.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $fines->links() }}</div>
</div>
