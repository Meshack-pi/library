<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'book_id', 'status', 'ready_at', 'expires_at'])]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'ready_at' => 'datetime',
            'expires_at' => 'datetime',
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
     * Reservations still in play: waiting for a copy, or with a copy held.
     *
     * @param  Builder<Reservation>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Ready]);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [ReservationStatus::Pending, ReservationStatus::Ready], true);
    }
}
