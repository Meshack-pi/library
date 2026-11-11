<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'author', 'isbn', 'category_id', 'shelf_location', 'total_copies'])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Loans that have not been returned yet.
     *
     * @return HasMany<Loan, $this>
     */
    public function activeLoans(): HasMany
    {
        return $this->loans()->whereNull('returned_at');
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Reservations waiting for a copy to come back.
     *
     * @return HasMany<Reservation, $this>
     */
    public function pendingReservations(): HasMany
    {
        return $this->reservations()->where('status', ReservationStatus::Pending);
    }

    /**
     * Reservations with a copy held on the shelf for the member.
     *
     * @return HasMany<Reservation, $this>
     */
    public function readyReservations(): HasMany
    {
        return $this->reservations()->where('status', ReservationStatus::Ready);
    }

    /**
     * Load the counts availableCopies() needs, so lists avoid a query per book.
     *
     * @param  Builder<Book>  $query
     */
    #[Scope]
    protected function withAvailability(Builder $query): void
    {
        $query->withCount(['activeLoans', 'readyReservations', 'pendingReservations']);
    }

    /**
     * Match books by title, author or ISBN.
     *
     * @param  Builder<Book>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('author', 'like', "%{$term}%")
                ->orWhere('isbn', 'like', "%{$term}%");
        });
    }

    /**
     * Only books with at least one copy free to borrow.
     *
     * @param  Builder<Book>  $query
     */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->whereRaw(
            'total_copies > (select count(*) from loans where loans.book_id = books.id and loans.returned_at is null)'
            .' + (select count(*) from reservations where reservations.book_id = books.id and reservations.status = ?)',
            [ReservationStatus::Ready->value],
        );
    }

    /**
     * Find a book by the ISBN or book ID typed at the desk.
     */
    public static function findByIdentifier(string $identifier): ?self
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        return static::query()->where('isbn', $identifier)->first()
            ?? (ctype_digit($identifier) ? static::query()->find((int) $identifier) : null);
    }

    /**
     * Copies anyone can borrow now: total, minus those on loan, minus those held for reservations.
     */
    public function availableCopies(): int
    {
        $onLoan = $this->attributes['active_loans_count'] ?? $this->activeLoans()->count();
        $held = $this->attributes['ready_reservations_count'] ?? $this->readyReservations()->count();

        return max(0, $this->total_copies - $onLoan - $held);
    }
}
