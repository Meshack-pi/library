<div>
    <h1 class="page-title">Reservations</h1>
    <p class="mt-1 text-sm text-ink-600">When a reserved book is returned, a copy is held for the member who has waited longest.</p>

    <div class="mt-4">
        <x-flash :notice="$notice" :failure="$failure" :version="$flashVersion" />
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Member</th>
                    <th>Reserved on</th>
                    <th>Status</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reservations as $reservation)
                    @php($isReady = $reservation->status === \App\Enums\ReservationStatus::Ready)
                    <tr wire:key="reservation-{{ $reservation->id }}">
                        <td class="font-medium">{{ $reservation->book->title }}</td>
                        <td>{{ $reservation->user->name }} <span class="text-ink-500">{{ $reservation->user->admission_number }}</span></td>
                        <td>{{ $reservation->created_at->format('j M Y') }}</td>
                        <td>
                            <span @class(['badge', 'badge-green' => $isReady, 'badge-amber' => ! $isReady])>{{ $reservation->status->label() }}</span>
                            @if ($isReady)
                                <span class="ml-1 text-xs text-ink-500">held until {{ $reservation->expires_at->format('j M Y') }}</span>
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            @if ($isReady)
                                <a href="{{ route('librarian.circulation', ['member' => $reservation->user->admission_number ?? $reservation->user->email, 'book' => $reservation->book->isbn]) }}"
                                   class="btn btn-primary btn-sm"><x-icon name="arrow-up-tray" class="size-4" /> Issue</a>
                            @endif
                            <button wire:click="cancel({{ $reservation->id }})" wire:confirm="Cancel this reservation?" type="button" class="btn btn-danger btn-sm"><x-icon name="x" class="size-4" /> Cancel</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">There are no active reservations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
