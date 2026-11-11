<?php

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Register')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $admission_number = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Create a member account. Other roles are only ever assigned by staff.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'admission_number' => ['nullable', 'string', 'max:50', 'unique:users,admission_number'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'admission_number' => $validated['admission_number'] ?: null,
            'password' => $validated['password'],
        ]);

        $user->assignRole(Role::Member->value);

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute($user->homeRoute());
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
