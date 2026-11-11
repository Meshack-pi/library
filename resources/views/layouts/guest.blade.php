<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 text-slate-900 antialiased">
    <div class="w-full max-w-md">
        <h1 class="mb-6 text-center text-2xl font-semibold text-indigo-700">{{ config('app.name') }}</h1>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
