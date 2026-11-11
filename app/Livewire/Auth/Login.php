<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Log in')]
class Login extends Component
{
    private const int MAX_ATTEMPTS = 5;

    #[Validate('required|string')]
    public string $login = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Sign the user in with either their email or admission number.
     */
    public function authenticate(): void
    {
        $this->validate();

        $throttleKey = Str::lower($this->login).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'login' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        $field = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'admission_number';

        if (! Auth::attempt([$field => $this->login, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        $this->redirectIntended(route($user->homeRoute()));
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
