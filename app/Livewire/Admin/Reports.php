<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reports')]
class Reports extends Component
{
    private const int TREND_MONTHS = 6;

    public function render(): View
    {
        Gate::authorize('reports.view');

        $overdue = Loan::query()->overdue()->with(['user', 'book'])->orderBy('due_at')->get();

        return view('livewire.admin.reports', [
            'totals' => [
                'Titles' => Book::count(),
                'Copies' => (int) Book::sum('total_copies'),
                'Members' => User::role(Role::Member->value)->count(),
                'Books on loan' => Loan::query()->active()->count(),
            ],
            'mostBorrowed' => Book::withTrashed()->has('loans')->withCount('loans')->orderByDesc('loans_count')->orderBy('title')->limit(10)->get(),
            'monthlyLoans' => $this->monthlyLoans(),
            'overdue' => $overdue,
            'finesCollected' => (int) Fine::query()->whereNotNull('paid_at')->sum('amount'),
            'finesCollectedThisMonth' => (int) Fine::query()->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'finesUnpaid' => (int) Fine::query()->unpaid()->sum('amount'),
            'finesAccruing' => $overdue->sum(fn (Loan $loan): int => $loan->accruedFine()),
        ]);
    }

    /**
     * Loans issued in each of the last few months, oldest first.
     *
     * @return Collection<string, int>
     */
    private function monthlyLoans(): Collection
    {
        $start = now()->startOfMonth()->subMonths(self::TREND_MONTHS - 1);

        $counts = Loan::query()
            ->where('issued_at', '>=', $start)
            ->pluck('issued_at')
            ->countBy(fn ($issuedAt): string => $issuedAt->format('Y-m'));

        return collect(range(0, self::TREND_MONTHS - 1))->mapWithKeys(function (int $offset) use ($start, $counts): array {
            $month = $start->copy()->addMonths($offset);

            return [$month->format('M Y') => $counts->get($month->format('Y-m'), 0)];
        });
    }
}
