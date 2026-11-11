<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Livewire\Concerns\Flashes;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Librarians')]
class Librarians extends Component
{
    use Flashes;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $librarianId): void
    {
        $librarian = $this->librarians()->findOrFail($librarianId);

        $this->resetForm();
        $this->editingId = $librarian->id;
        $this->name = $librarian->name;
        $this->email = $librarian->email;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        Gate::authorize('librarians.manage');

        $librarian = $this->editingId ? $this->librarians()->findOrFail($this->editingId) : null;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($librarian)],
            'password' => [$librarian ? 'nullable' : 'required', Password::defaults()],
        ]);

        $attributes = ['name' => $validated['name'], 'email' => $validated['email']];

        if ($validated['password']) {
            $attributes['password'] = $validated['password'];
        }

        if ($librarian) {
            $librarian->update($attributes);
        } else {
            $librarian = User::create($attributes);
            $librarian->assignRole(Role::Librarian->value);
        }

        $this->flashSuccess("Saved {$librarian->name}.");
        $this->resetForm();
    }

    /**
     * Take away staff access. The account stays, as an ordinary member, so its loan history is kept.
     */
    public function revoke(int $librarianId): void
    {
        Gate::authorize('librarians.manage');

        $librarian = $this->librarians()->findOrFail($librarianId);
        $librarian->syncRoles(Role::Member->value);

        $this->flashSuccess("{$librarian->name} is no longer a librarian.");
    }

    public function render(): View
    {
        Gate::authorize('librarians.manage');

        return view('livewire.admin.librarians', [
            'librarians' => $this->librarians()->orderBy('name')->get(),
        ]);
    }

    /**
     * @return Builder<User>
     */
    private function librarians(): Builder
    {
        return User::role(Role::Librarian->value);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'email', 'password']);
        $this->resetValidation();
    }
}
