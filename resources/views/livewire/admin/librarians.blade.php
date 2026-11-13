<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="page-title">Librarians</h1>
        <button wire:click="create" type="button" class="btn btn-primary"><x-icon name="user-plus" /> Add librarian</button>
    </div>

    <div class="mt-4">
        <x-flash :notice="$notice" :failure="$failure" :version="$flashVersion" />
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card mb-6">
            <h2 class="card-title">{{ $editingId ? 'Update librarian' : 'Add a librarian' }}</h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="name" class="label">Full name</label>
                    <input wire:model="name" id="name" type="text" class="input" required>
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="label">Email</label>
                    <input wire:model="email" id="email" type="email" class="input" required>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="label">{{ $editingId ? 'New password (optional)' : 'Password' }}</label>
                    <input wire:model="password" id="password" type="password" class="input" autocomplete="new-password" @required(! $editingId)>
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex gap-2">
                <button type="submit" class="btn btn-dark"><x-icon name="check" /> Save librarian</button>
                <button wire:click="cancel" type="button" class="btn btn-secondary"><x-icon name="x" /> Cancel</button>
            </div>
        </form>
    @endif

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Added</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($librarians as $librarian)
                    <tr wire:key="librarian-{{ $librarian->id }}">
                        <td class="font-medium">{{ $librarian->name }}</td>
                        <td>{{ $librarian->email }}</td>
                        <td>{{ $librarian->created_at->format('j M Y') }}</td>
                        <td class="text-right whitespace-nowrap">
                            <button wire:click="edit({{ $librarian->id }})" type="button" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="size-4" /> Edit</button>
                            <button wire:click="revoke({{ $librarian->id }})" wire:confirm="Remove librarian access for {{ $librarian->name }}? Their account becomes an ordinary member."
                                    type="button" class="btn btn-danger btn-sm"><x-icon name="trash" class="size-4" /> Remove access</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No librarians yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
