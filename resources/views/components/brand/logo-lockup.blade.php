{{--
    Brand lockup: the Qbitio mark, optionally followed by the wordmark.

    Previously an inline SVG of the old mark and wordmark. Callers pass
    only a size and a text colour, so the root is a span and the colour applies
    to the wordmark; the mark handles its own dark-mode treatment.
--}}
@props([
    'showWordmark' => true,
    'size' => 'md',
])

@php
    $markSizes = ['sm' => 'sm', 'md' => 'md', 'lg' => 'lg'];
    $textSizes = ['sm' => 'text-base', 'md' => 'text-lg', 'lg' => 'text-xl'];

    $markSize = $markSizes[$size] ?? $markSizes['md'];
    $textSize = $textSizes[$size] ?? $textSizes['md'];
@endphp

<span {{ $attributes->class('inline-flex items-center gap-2') }}>
    <x-brand.qbitio-mark :size="$markSize" />

    @if ($showWordmark)
        <span class="{{ $textSize }} font-semibold tracking-tight">Qbitio</span>
    @endif
</span>
