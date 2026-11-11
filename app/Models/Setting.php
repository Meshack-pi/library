<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * The library rules and the values used until an admin changes them.
     *
     * @var array<string, int>
     */
    public const array DEFAULTS = [
        'loan_days' => 14,
        'fine_per_day' => 10,
        'max_books' => 3,
        'max_renewals' => 2,
        'hold_days' => 3,
    ];

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Read a library rule, falling back to its default.
     */
    public static function value(string $key): int
    {
        $stored = static::query()->whereKey($key)->value('value');

        return (int) ($stored ?? self::DEFAULTS[$key]);
    }

    /**
     * Every library rule with its current value.
     *
     * @return array<string, int>
     */
    public static function allValues(): array
    {
        $stored = static::query()->pluck('value', 'key');

        return collect(self::DEFAULTS)
            ->map(fn (int $default, string $key): int => (int) ($stored[$key] ?? $default))
            ->all();
    }

    public static function put(string $key, int $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }
}
