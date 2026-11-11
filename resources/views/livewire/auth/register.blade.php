<form wire:submit="register" class="space-y-4">
    <h2 class="text-lg font-semibold">Create a member account</h2>

    @foreach ([
        ['name', 'Full name', 'text', 'name'],
        ['email', 'Email', 'email', 'email'],
        ['admission_number', 'Admission or staff number (optional)', 'text', 'off'],
        ['password', 'Password', 'password', 'new-password'],
        ['password_confirmation', 'Confirm password', 'password', 'new-password'],
    ] as [$field, $label, $type, $autocomplete])
        <div wire:key="field-{{ $field }}">
            <label for="{{ $field }}" class="mb-1 block text-sm font-medium">{{ $label }}</label>
            <input wire:model="{{ $field }}" id="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}"
                   @required($field !== 'admission_number')
                   class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endforeach

    <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 disabled:opacity-60"
            wire:loading.attr="disabled">
        Register
    </button>

    <p class="text-center text-sm text-slate-600">
        Already registered? <a href="{{ route('login') }}" class="text-indigo-700 hover:underline">Log in</a>
    </p>
</form>
