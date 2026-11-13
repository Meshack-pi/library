<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-ink-900 bg-[radial-gradient(circle_at_top,_rgb(214_146_94/0.25),_transparent_60%)] px-4 py-10 text-ink-900 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-6 flex flex-col items-center gap-3 text-white">
            <span class="flex size-16 items-center justify-center rounded-2xl bg-copper-500 text-ink-950 shadow-lg"><x-icon name="book-open" class="size-9" /></span>
            <h1 class="text-3xl font-extrabold tracking-tight">{{ config('app.name') }}</h1>
            <p class="text-sm text-ink-200">Borrow, reserve and track your books in one place.</p>
        </div>

        <div class="rounded-3xl border-t-8 border-copper-500 bg-white p-7 shadow-2xl">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
