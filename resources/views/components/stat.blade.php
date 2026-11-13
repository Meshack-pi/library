@props(['label', 'icon', 'color' => 'ink'])

@php
    /**
     * Icon tile styles for each brand tone.
     *
     * @var array<string, string> $tiles
     */
    $tiles = [
        'ink' => 'bg-ink-900 text-copper-400',
        'copper' => 'bg-copper-500 text-ink-950',
        'sage' => 'bg-ink-100 text-ink-800',
        'sand' => 'bg-copper-100 text-copper-800',
        'rose' => 'bg-rose-100 text-rose-700',
    ];
@endphp

<div {{ $attributes->class('stat') }}>
    <span @class(['stat-icon', $tiles[$color] ?? $tiles['ink']])><x-icon :name="$icon" class="size-6" /></span>
    <div class="min-w-0">
        <p class="stat-label">{{ $label }}</p>
        <p {{ $value->attributes->class('stat-value') }}>{{ $value }}</p>
    </div>
</div>
