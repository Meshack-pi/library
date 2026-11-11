<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="page-title">Librarian desk</h1>
        <div class="flex gap-2">
            <a href="{{ route('librarian.circulation') }}" class="btn btn-primary">Issue book</a>
            <a href="{{ route('librarian.circulation') }}#return" class="btn btn-secondary">Return book</a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat">
            <p class="stat-label">Books on loan</p>
            <p class="stat-value">{{ $onLoan }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Due today</p>
            <p class="stat-value">{{ $dueToday->count() }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Overdue</p>
            <p @class(['stat-value', 'text-red-700' => $overdue->isNotEmpty()])>{{ $overdue->count() }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Unpaid fines</p>
            <p class="stat-value">KES {{ number_format($unpaidFines) }}</p>
        </div>
    </div>

    <h2 class="mt-8 mb-3 text-lg font-semibold">Due back today</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Book</th>
                    <th>Issued</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dueToday as $loan)
                    <tr wire:key="due-{{ $loan->id }}">
                        <td>{{ $loan->user->name }} <span class="text-slate-500">{{ $loan->user->admission_number }}</span></td>
                        <td>{{ $loan->book->title }}</td>
                        <td>{{ $loan->issued_at->format('j M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty">Nothing is due today.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="mt-8 mb-3 text-lg font-semibold">Overdue</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Book</th>
                    <th>Due</th>
                    <th>Days late</th>
                    <th>Fine so far</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($overdue as $loan)
                    <tr wire:key="overdue-{{ $loan->id }}">
                        <td>{{ $loan->user->name }} <span class="text-slate-500">{{ $loan->user->admission_number }}</span></td>
                        <td>{{ $loan->book->title }}</td>
                        <td>{{ $loan->due_at->format('j M Y') }}</td>
                        <td><span class="badge badge-red">{{ $loan->daysOverdue() }}</span></td>
                        <td>KES {{ number_format($loan->accruedFine()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No overdue books.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="mt-8 mb-3 text-lg font-semibold">Reserved copies waiting for collection</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Book</th>
                    <th>Held until</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($holds as $hold)
                    <tr wire:key="hold-{{ $hold->id }}">
                        <td>{{ $hold->user->name }} <span class="text-slate-500">{{ $hold->user->admission_number }}</span></td>
                        <td>{{ $hold->book->title }}</td>
                        <td>{{ $hold->expires_at->format('j M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty">No copies are being held.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
