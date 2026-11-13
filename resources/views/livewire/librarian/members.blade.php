<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="page-title">Members</h1>
        <button wire:click="create" type="button" class="btn btn-primary"><x-icon name="user-plus" /> Register member</button>
    </div>

    <div class="mt-4">
        <x-flash :notice="$notice" :failure="$failure" :version="$flashVersion" />
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card mb-6">
            <h2 class="card-title">{{ $editingId ? 'Update member' : 'Register a member' }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
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
                    <label for="admission_number" class="label">Admission or staff number</label>
                    <input wire:model="admission_number" id="admission_number" type="text" class="input">
                    @error('admission_number') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="label">{{ $editingId ? 'New password (leave blank to keep the current one)' : 'Password' }}</label>
                    <input wire:model="password" id="password" type="password" class="input" autocomplete="new-password" @required(! $editingId)>
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex gap-2">
                <button type="submit" class="btn btn-dark"><x-icon name="check" /> Save member</button>
                <button wire:click="cancel" type="button" class="btn btn-secondary"><x-icon name="x" /> Cancel</button>
            </div>
        </form>
    @endif

    <div class="mb-3 max-w-md">
        <label for="search" class="sr-only">Search members</label>
        <input wire:model.live.debounce.300ms="search" id="search" type="search" class="input" placeholder="Search by name, email or admission number">
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Admission no.</th>
                    <th>Email</th>
                    <th>Books on loan</th>
                    <th>Fines owed</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr wire:key="member-{{ $member->id }}">
                        <td class="font-medium">{{ $member->name }}</td>
                        <td>{{ $member->admission_number ?? '—' }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->active_loans_count }}</td>
                        <td>
                            @if ($member->unpaid_fines_sum_amount > 0)
                                <span class="text-rose-700">KES {{ number_format($member->unpaid_fines_sum_amount) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('librarian.circulation', ['member' => $member->admission_number ?? $member->email]) }}" class="btn btn-primary btn-sm"><x-icon name="arrow-up-tray" class="size-4" /> Issue book</a>
                            <button wire:click="edit({{ $member->id }})" type="button" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="size-4" /> Edit</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No members found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $members->links() }}</div>
</div>
