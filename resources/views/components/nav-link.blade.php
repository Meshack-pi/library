@props(['route', 'icon'])

@php($isActive = request()->routeIs($route, $route.'.*'))

<a href="{{ route($route) }}" @if ($isActive) aria-current="page" @endif @class(['nav-link', 'nav-link-active' => $isActive])>
    <span class="nav-link-icon"><x-icon :name="$icon" /></span>
    <span>{{ $slot }}</span>
</a>
