<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting',
            self::Ready => 'Ready to collect',
            self::Fulfilled => 'Collected',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }
}
