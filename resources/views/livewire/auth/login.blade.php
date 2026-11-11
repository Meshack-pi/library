<form wire:submit="authenticate" class="space-y-4">
    <h2 class="text-lg font-semibold">Log in</h2>

    <div>
        <label for="login" class="mb-1 block text-sm font-medium">Email or admission number</label>
        <input wire:model="login" id="login" type="text" autocomplete="username" autofocus required
               class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        @error('login') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="mb-1 block text-sm font-medium">Password</label>
        <input wire:model="password" id="password" type="password" autocomplete="current-password" required
               class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input wire:model="remember" type="checkbox" class="rounded border-slate-300">
        Remember me
    </label>

    <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 disabled:opacity-60"
            wire:loading.attr="disabled">
        Log in
    </button>

    <p class="text-center text-sm text-slate-600">
        New here? <a href="{{ route('register') }}" class="text-indigo-700 hover:underline">Create an account</a>
    </p>
</form>
