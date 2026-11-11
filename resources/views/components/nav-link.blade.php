@props(['route'])

<a href="{{ route($route) }}" @class(['nav-link', 'nav-link-active' => request()->routeIs($route, $route.'.*')])>{{ $slot }}</a>
