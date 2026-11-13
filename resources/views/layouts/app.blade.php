<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-50 text-ink-900 antialiased">
    @php($user = auth()->user())

    <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="min-h-screen lg:pl-72">
        {{-- Mobile top bar --}}
        <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-ink-100 bg-white/90 px-4 py-3 backdrop-blur lg:hidden">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-lg font-extrabold text-ink-900">
                <span class="flex size-9 items-center justify-center rounded-xl bg-ink-900 text-copper-400"><x-icon name="book-open" /></span>
                {{ config('app.name') }}
            </a>
            <button type="button" class="btn btn-secondary btn-sm" @click="sidebarOpen = true" aria-controls="sidebar" :aria-expanded="sidebarOpen">
                <x-icon name="menu" />
                <span>Menu</span>
            </button>
        </header>

        {{-- Backdrop for the mobile drawer --}}
        <div x-show="sidebarOpen" x-transition.opacity x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-ink-950/60 lg:hidden"></div>

        <aside id="sidebar"
               :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': ! sidebarOpen }"
               class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-ink-900 text-white shadow-xl transition-transform duration-200 lg:translate-x-0"
               aria-label="Sidebar">
            <div class="flex items-center justify-between gap-2 px-5 pt-6 pb-2">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-xl font-extrabold">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-copper-500 text-ink-950 shadow-md"><x-icon name="book-open" class="size-6" /></span>
                    {{ config('app.name') }}
                </a>
                <button type="button" class="rounded-lg p-2 text-ink-200 hover:bg-white/10 hover:text-white lg:hidden" @click="sidebarOpen = false">
                    <x-icon name="x" />
                    <span class="sr-only">Close menu</span>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 pb-4" aria-label="Main">
                @role('member')
                    <p class="sidebar-heading">My account</p>
                    <x-nav-link route="member.dashboard" icon="home">My library</x-nav-link>
                @endrole

                @can('catalogue.view')
                    <p class="sidebar-heading">Browse</p>
                    <x-nav-link route="catalogue.index" icon="books">Catalogue</x-nav-link>
                @endcan

                @can('loans.issue')
                    <p class="sidebar-heading">Circulation</p>
                    <div class="space-y-1">
                        <x-nav-link route="librarian.dashboard" icon="desk">Desk</x-nav-link>
                        <x-nav-link route="librarian.circulation" icon="arrows">Issue / Return</x-nav-link>
                        @can('reservations.manage')
                            <x-nav-link route="librarian.reservations" icon="bookmark">Reservations</x-nav-link>
                        @endcan
                        @can('fines.record-payment')
                            <x-nav-link route="librarian.fines" icon="banknotes">Fines</x-nav-link>
                        @endcan
                    </div>
                @endcan

                @canany(['books.manage', 'members.manage'])
                    <p class="sidebar-heading">Manage</p>
                    <div class="space-y-1">
                        @can('books.manage')
                            <x-nav-link route="librarian.books" icon="book-open">Books</x-nav-link>
                        @endcan
                        @can('members.manage')
                            <x-nav-link route="librarian.members" icon="users">Members</x-nav-link>
                        @endcan
                    </div>
                @endcanany

                @canany(['reports.view', 'librarians.manage', 'settings.manage'])
                    <p class="sidebar-heading">Administration</p>
                    <div class="space-y-1">
                        @can('reports.view')
                            <x-nav-link route="admin.reports" icon="chart">Reports</x-nav-link>
                        @endcan
                        @can('librarians.manage')
                            <x-nav-link route="admin.librarians" icon="shield">Librarians</x-nav-link>
                        @endcan
                        @can('settings.manage')
                            <x-nav-link route="admin.settings" icon="cog">Rules</x-nav-link>
                        @endcan
                    </div>
                @endcanany
            </nav>

            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-copper-500 text-base font-extrabold text-ink-950">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold">{{ $user->name }}</p>
                        <p class="truncate text-xs text-copper-300 capitalize">{{ $user->getRoleNames()->first() ?? 'member' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-primary w-full">
                        <x-icon name="logout" />
                        Log out
                    </button>
                </form>
            </div>
        </aside>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-10 lg:py-10">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
