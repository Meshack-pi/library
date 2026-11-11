<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'book_id', 'issued_by', 'issued_at', 'due_at', 'returned_at', 'renewals_count'])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_at' => 'date',
            'returned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return HasOne<Fine, $this>
     */
    public function fine(): HasOne
    {
        return $this->hasOne(Fine::class);
    }

    /**
     * @param  Builder<Loan>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('returned_at');
    }

    /**
     * @param  Builder<Loan>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereNull('returned_at')->whereDate('due_at', '<', today());
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isReturned() && $this->daysOverdue() > 0;
    }

    /**
     * Whole days past the due date, counted up to the return date (or today if still out).
     */
    public function daysOverdue(?CarbonInterface $asOf = null): int
    {
        $end = ($this->returned_at ?? $asOf ?? now())->copy()->startOfDay();

        return max(0, (int) $this->due_at->copy()->startOfDay()->diffInDays($end, false));
    }

    /**
     * The fine owed so far, in KES: days overdue multiplied by the daily rate.
     */
    public function accruedFine(?CarbonInterface $asOf = null): int
    {
        return $this->daysOverdue($asOf) * Setting::value('fine_per_day');
    }
}
