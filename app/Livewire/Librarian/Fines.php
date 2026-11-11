<?php

namespace App\Livewire\Librarian;

use App\Exceptions\LoanException;
use App\Livewire\Concerns\Flashes;
use App\Models\Fine;
use App\Services\LoanService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Fines')]
class Fines extends Component
{
    use Flashes, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'unpaid';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function recordPayment(int $fineId): void
    {
        Gate::authorize('fines.record-payment');

        $fine = Fine::with('user')->findOrFail($fineId);

        try {
            app(LoanService::class)->recordFinePayment($fine, Auth::user());
            $this->flashSuccess('Recorded payment of KES '.number_format($fine->amount)." from {$fine->user->name}.");
        } catch (LoanException $exception) {
            $this->flashError($exception->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('fines.record-payment');

        $term = trim($this->search);

        return view('livewire.librarian.fines', [
            'fines' => Fine::query()
                ->when($this->status === 'unpaid', fn (Builder $query) => $query->whereNull('paid_at'))
                ->when($this->status === 'paid', fn (Builder $query) => $query->whereNotNull('paid_at'))
                ->when($term !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $user) => $user
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('admission_number', 'like', "%{$term}%")))
                ->with(['user', 'loan.book', 'receiver'])
                ->latest()
                ->paginate(15),
            'totalUnpaid' => (int) Fine::query()->unpaid()->sum('amount'),
        ]);
    }
}
