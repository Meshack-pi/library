<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'admission_number', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The route name of the dashboard matching the user's highest role.
     */
    public function homeRoute(): string
    {
        foreach (Role::cases() as $role) {
            if ($this->hasRole($role->value)) {
                return $role->homeRoute();
            }
        }

        return Role::Member->homeRoute();
    }

    /**
     * Find a user by the email or admission number typed at the desk.
     */
    public static function findByIdentifier(string $identifier): ?self
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        return static::query()
            ->where('admission_number', $identifier)
            ->orWhere('email', strtolower($identifier))
            ->first();
    }

    /**
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Loans the member has not returned yet.
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
     * @return HasMany<Fine, $this>
     */
    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class);
    }

    /**
     * Fines the member has not paid yet.
     *
     * @return HasMany<Fine, $this>
     */
    public function unpaidFines(): HasMany
    {
        return $this->fines()->whereNull('paid_at');
    }
}
