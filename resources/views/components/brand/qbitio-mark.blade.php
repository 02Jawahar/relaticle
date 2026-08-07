{{--
    Qbitio mark — a blocky "Q": a square ring with a stepped opening on the
    right and a detached tail block at the bottom-right.

    PLACEHOLDER: hand-drawn from the supplied image, not the original asset. Drop
    the real file in and point this component at it when you have it; every call
    site goes through <x-brand.qbitio-mark /> so nothing else needs touching.
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

<svg
    viewBox="0 0 32 32"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-label="Qbitio"
    {{ $attributes->class($sizeClass) }}
>
    {{-- Square ring with the top-right and bottom-right steps cut out --}}
    <path
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M4 4h18v5H9v14h9v5H4V4Zm18 5h5v9h-5V9Z"
        fill="currentColor"
    />
    {{-- Tail block --}}
    <rect x="21" y="20" width="7" height="7" rx="1" fill="currentColor" />
</svg>
