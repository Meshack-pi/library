<?php

namespace App\Livewire\Librarian;

use App\Enums\Role;
use App\Livewire\Concerns\Flashes;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Members')]
class Members extends Component
{
    use Flashes, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $admission_number = '';

    public string $password = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $memberId): void
    {
        $member = $this->members()->findOrFail($memberId);

        $this->resetForm();
        $this->editingId = $member->id;
        $this->name = $member->name;
        $this->email = $member->email;
        $this->admission_number = (string) $member->admission_number;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        Gate::authorize('members.manage');

        $member = $this->editingId ? $this->members()->findOrFail($this->editingId) : null;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member)],
            'admission_number' => ['nullable', 'string', 'max:50', Rule::unique('users', 'admission_number')->ignore($member)],
            'password' => [$member ? 'nullable' : 'required', Password::defaults()],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'admission_number' => $validated['admission_number'] ?: null,
        ];

        if ($validated['password']) {
            $attributes['password'] = $validated['password'];
        }

        if ($member) {
            $member->update($attributes);
        } else {
            $member = User::create($attributes);
            $member->assignRole(Role::Member->value);
        }

        $this->flashSuccess("Saved {$member->name}.");
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize('members.manage');

        $term = trim($this->search);

        return view('livewire.librarian.members', [
            'members' => $this->members()
                ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('admission_number', 'like', "%{$term}%")))
                ->withCount('activeLoans')
                ->withSum('unpaidFines', 'amount')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    /**
     * Librarians manage member accounts only, never staff accounts.
     *
     * @return Builder<User>
     */
    private function members(): Builder
    {
        return User::role(Role::Member->value);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'email', 'admission_number', 'password']);
        $this->resetValidation();
    }
}
