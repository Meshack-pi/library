<form wire:submit="register" class="space-y-4">
    <div>
        <h2 class="text-2xl font-extrabold text-ink-900">Create a member account</h2>
        <p class="mt-1 text-sm text-ink-500">It only takes a minute to start borrowing.</p>
    </div>

    @foreach ([
        ['name', 'Full name', 'text', 'name'],
        ['email', 'Email', 'email', 'email'],
        ['admission_number', 'Admission or staff number (optional)', 'text', 'off'],
        ['password', 'Password', 'password', 'new-password'],
        ['password_confirmation', 'Confirm password', 'password', 'new-password'],
    ] as [$field, $label, $type, $autocomplete])
        <div wire:key="field-{{ $field }}">
            <label for="{{ $field }}" class="label">{{ $label }}</label>
            <input wire:model="{{ $field }}" id="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}"
                   @required($field !== 'admission_number')
                   class="input">
            @error($field) <p class="field-error">{{ $message }}</p> @enderror
        </div>
    @endforeach

    <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">
        <x-icon name="user-plus" />
        Register
    </button>

    <p class="text-center text-sm text-ink-600">
        Already registered? <a href="{{ route('login') }}" class="link">Log in</a>
    </p>
</form>
