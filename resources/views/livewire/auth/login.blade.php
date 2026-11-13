<form wire:submit="authenticate" class="space-y-4">
    <div>
        <h2 class="text-2xl font-extrabold text-ink-900">Welcome back</h2>
        <p class="mt-1 text-sm text-ink-500">Log in to continue to your library.</p>
    </div>

    <div>
        <label for="login" class="label">Email or admission number</label>
        <input wire:model="login" id="login" type="text" autocomplete="username" autofocus required class="input">
        @error('login') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="label">Password</label>
        <input wire:model="password" id="password" type="password" autocomplete="current-password" required class="input">
        @error('password') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input wire:model="remember" type="checkbox" class="size-4 rounded border-ink-300 accent-copper-600">
        Remember me
    </label>

    <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">
        <x-icon name="login" />
        Log in
    </button>

    <p class="text-center text-sm text-ink-600">
        New here? <a href="{{ route('register') }}" class="link">Create an account</a>
    </p>
</form>
