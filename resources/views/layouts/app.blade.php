<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 pt-3">
            <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-indigo-700">{{ config('app.name') }}</a>

            <div class="flex items-center gap-3 text-sm">
                <span class="text-slate-500">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Log out</button>
                </form>
            </div>
        </div>

        <nav class="mx-auto flex max-w-6xl flex-wrap items-center gap-1 px-2 py-2 text-sm" aria-label="Main">
            @role('member')
                <x-nav-link route="member.dashboard">My library</x-nav-link>
            @endrole
            @can('catalogue.view')
                <x-nav-link route="catalogue.index">Catalogue</x-nav-link>
            @endcan
            @can('loans.issue')
                <x-nav-link route="librarian.dashboard">Desk</x-nav-link>
                <x-nav-link route="librarian.circulation">Issue / Return</x-nav-link>
            @endcan
            @can('books.manage')
                <x-nav-link route="librarian.books">Books</x-nav-link>
            @endcan
            @can('members.manage')
                <x-nav-link route="librarian.members">Members</x-nav-link>
            @endcan
            @can('reservations.manage')
                <x-nav-link route="librarian.reservations">Reservations</x-nav-link>
            @endcan
            @can('fines.record-payment')
                <x-nav-link route="librarian.fines">Fines</x-nav-link>
            @endcan
            @can('reports.view')
                <x-nav-link route="admin.reports">Reports</x-nav-link>
            @endcan
            @can('librarians.manage')
                <x-nav-link route="admin.librarians">Librarians</x-nav-link>
            @endcan
            @can('settings.manage')
                <x-nav-link route="admin.settings">Rules</x-nav-link>
            @endcan
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        {{ $slot }}
    </main>
</body>
</html>
