<div>
    <h1 class="page-title">Reports</h1>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($totals as $label => $value)
            <div class="stat">
                <p class="stat-label">{{ $label }}</p>
                <p class="stat-value">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card">
            <h2 class="card-title">Most borrowed books</h2>
            @php($topCount = max(1, (int) $mostBorrowed->max('loans_count')))
            <ol class="space-y-3 text-sm">
                @forelse ($mostBorrowed as $book)
                    <li>
                        <div class="flex justify-between gap-3">
                            <span class="truncate">{{ $book->title }}</span>
                            <span class="font-medium">{{ $book->loans_count }}</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-indigo-500" style="width: {{ round($book->loans_count / $topCount * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="empty">No books have been borrowed yet.</li>
                @endforelse
            </ol>
        </section>

        <section class="card">
            <h2 class="card-title">Loans issued per month</h2>
            @php($topMonth = max(1, (int) $monthlyLoans->max()))
            <div class="flex h-48 items-end gap-3" role="img" aria-label="Bar chart of loans issued in each of the last six months">
                @foreach ($monthlyLoans as $month => $count)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-1 text-xs">
                        <span class="font-medium">{{ $count }}</span>
                        <div class="w-full rounded-t bg-indigo-500" style="height: {{ round($count / $topMonth * 80) }}%"></div>
                        <span class="whitespace-nowrap text-slate-500">{{ $month }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <h2 class="mt-8 mb-3 text-lg font-semibold">Fines</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat">
            <p class="stat-label">Collected this month</p>
            <p class="stat-value">KES {{ number_format($finesCollectedThisMonth) }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Collected in total</p>
            <p class="stat-value">KES {{ number_format($finesCollected) }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Charged but unpaid</p>
            <p class="stat-value">KES {{ number_format($finesUnpaid) }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Building up on overdue books</p>
            <p class="stat-value">KES {{ number_format($finesAccruing) }}</p>
        </div>
    </div>

    <h2 class="mt-8 mb-3 text-lg font-semibold">Overdue books ({{ $overdue->count() }})</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Member</th>
                    <th>Due</th>
                    <th>Days late</th>
                    <th>Fine so far</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($overdue as $loan)
                    <tr wire:key="overdue-{{ $loan->id }}">
                        <td class="font-medium">{{ $loan->book->title }}</td>
                        <td>{{ $loan->user->name }} <span class="text-slate-500">{{ $loan->user->admission_number }}</span></td>
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
</div>
