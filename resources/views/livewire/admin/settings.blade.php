<div>
    <h1 class="page-title">Library rules</h1>

    <div class="mt-4">
        <x-flash :notice="$notice" :failure="$failure" :version="$flashVersion" />
    </div>

    <form wire:submit="save" class="card max-w-2xl">
        <div class="space-y-5">
            @foreach ($fields as $key => $field)
                <div wire:key="setting-{{ $key }}">
                    <label for="setting-{{ $key }}" class="label">{{ $field['label'] }}</label>
                    <input wire:model="values.{{ $key }}" id="setting-{{ $key }}" type="number" min="{{ $field['min'] }}" class="input max-w-40" required>
                    <p class="mt-1 text-xs text-ink-500">{{ $field['help'] }}</p>
                    @error("values.{$key}") <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-dark mt-6"><x-icon name="check" /> Save rules</button>
    </form>
</div>
