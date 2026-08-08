{{--
    The Qbitio mark, in the supplied artwork for each theme.

    Two images swapped by the `dark` class rather than one filtered with
    `dark:invert`: both files were provided, and an inverted black PNG is only
    ever an approximation of the real white one.

    The accessible name lives on the wrapper, so exactly one is announced
    whichever theme is active — an `alt` on each image would either double up or,
    with `aria-hidden` on one, leave dark mode with no name at all.

    Source art is 361x393 raster, which is sharp at these sizes; swap in an SVG
    at the same paths if the mark is ever needed larger than `lg`.
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

<span
    role="img"
    aria-label="{{ config('app.name') }}"
    {{ $attributes->class("inline-flex flex-shrink-0 items-center justify-center {$sizeClass}") }}
>
    <img
        src="{{ asset('images/qbitio-mark.png') }}"
        alt=""
        aria-hidden="true"
        class="h-full w-full object-contain dark:hidden"
    />

    <img
        src="{{ asset('images/qbitio-mark-dark.png') }}"
        alt=""
        aria-hidden="true"
        class="hidden h-full w-full object-contain dark:block"
    />
</span>
