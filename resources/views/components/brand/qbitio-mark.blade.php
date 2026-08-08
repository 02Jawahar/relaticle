{{--
    The Qbitio mark.

    Renders the supplied asset rather than a traced SVG, so the brand is exactly
    what was handed over. The source is a small raster (37x40), which is fine at
    the sizes used here but will soften on a high-DPI display above `lg` — swap
    in an SVG or a 2x export at public/images/qbitio-mark.png and nothing else
    needs to change.

    The mark is solid black on transparency, so it is inverted in dark mode to
    read as white instead of disappearing into the surface.
--}}
@props([
    'size' => 'md',
])

@php
    $sizeMap = [
        'xs' => 'h-5 w-5',
        'sm' => 'h-6 w-6',
        'md' => 'h-8 w-8',
        'lg' => 'h-10 w-10',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];
@endphp

<img
    src="{{ asset('images/qbitio-mark.png') }}"
    alt="{{ config('app.name') }}"
    {{ $attributes->class("object-contain dark:invert {$sizeClass}") }}
/>
