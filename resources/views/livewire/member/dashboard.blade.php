<div>
    <h1 class="page-title">My library</h1>
    <p class="mt-1 text-sm text-slate-600">
        Welcome, {{ auth()->user()->name }}.
        @if (auth()->user()->admission_number) Your member number is {{ auth()->user()->admission_number }}. @endif
    </p>

    <div class="mt-4">
        <x-flash :notice="$notice" :failure="$failure" />
    </div>

    @if ($notifications->isNotEmpty())
        <div class="card mb-6 border-indigo-200 bg-indigo-50">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold">Notifications</h2>
                <button wire:click="markNotificationsRead" type="button" class="btn btn-secondary btn-sm">Mark all as read</button>
            </div>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($notifications as $notification)
                    <li wire:key="notification-{{ $notification->id }}">
                        {{ $notification->data['message'] ?? '' }}
                        <span class="text-slate-500">· {{ $notification->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="stat">
            <p class="stat-label">Books on loan</p>
            <p class="stat-value">{{ $loans->count() }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Reservations</p>
            <p class="stat-value">{{ $reservations->count() }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Fines owed</p>
            <p @class(['stat-value', 'text-red-700' => $totalOwed > 0])>KES {{ number_format($totalOwed) }}</p>
        </div>
    </div>

    <h2 class="mt-8 mb-3 text-lg font-semibold">Current loans</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Borrowed</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr wire:key="loan-{{ $loan->id }}">
                        <td><a href="{{ route('catalogue.show', $loan->book_id) }}" class="font-medium text-indigo-700 hover:underline">{{ $loan->book->title }}</a></td>
                        <td>{{ $loan->issued_at->format('j M Y') }}</td>
                        <td>{{ $loan->due_at->format('j M Y') }}</td>
                        <td>
                            @if ($loan->isOverdue())
                                <span class="badge badge-red">{{ $loan->daysOverdue() }} days overdue</span>
                                <span class="ml-1 text-xs text-red-700">KES {{ number_format($loan->accruedFine()) }} so far</span>
                            @elseif ($loan->due_at->isToday())
                                <span class="badge badge-amber">Due today</span>
                            @else
                                <span class="badge badge-green">On time</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @unless ($loan->isOverdue())
                                <button wire:click="renew({{ $loan->id }})" type="button" class="btn btn-secondary btn-sm">Renew</button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">You have no books on loan. <a href="{{ route('catalogue.index') }}" class="text-indigo-700 hover:underline">Browse the catalogue</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="mt-8 mb-3 text-lg font-semibold">Reservations</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book</th>
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
                        <td>{{ $reservation->created_at->format('j M Y') }}</td>
                        <td>
                            <span @class(['badge', 'badge-green' => $isReady, 'badge-amber' => ! $isReady])>{{ $reservation->status->label() }}</span>
                            @if ($isReady)
                                <span class="ml-1 text-xs text-slate-500">collect by {{ $reservation->expires_at->format('j M Y') }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <button wire:click="cancelReservation({{ $reservation->id }})" wire:confirm="Cancel this reservation?" type="button" class="btn btn-danger btn-sm">Cancel</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">You have no reservations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="mt-8 mb-3 text-lg font-semibold">Fines</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Returned</th>
                    <th>Days late</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($unpaidFines as $fine)
                    <tr wire:key="fine-{{ $fine->id }}">
                        <td class="font-medium">{{ $fine->loan->book->title }}</td>
                        <td>{{ $fine->loan->returned_at?->format('j M Y') }}</td>
                        <td>{{ $fine->loan->daysOverdue() }}</td>
                        <td>KES {{ number_format($fine->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">You have no unpaid fines.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($totalOwed > 0)
        <p class="mt-3 text-sm text-slate-600">
            @if ($accruing > 0) Overdue books you still hold have added KES {{ number_format($accruing) }} so far. @endif
            Fines are paid at the librarian's desk.
        </p>
    @endif
</div>
