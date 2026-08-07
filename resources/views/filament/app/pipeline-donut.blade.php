{{--
    An animated donut for one pipeline's stage distribution.

    Plain inline SVG rather than a charting library: no extra bundle, and nothing
    for the CSP to block. Segments are arcs on one circle driven by
    stroke-dasharray, and each links to the pipeline's board so clicking a slice
    lands on the records it represents.

    Expects: $title, $subtitle, $centreLabel, $url, $stages (list of
    ['label','color','count']).
--}}
@php
    $all = collect($stages);
    $segments = $all->filter(fn (array $s): bool => $s['count'] > 0)->values();
    $total = (int) $all->sum('count');

    $radius = 42;
    $circumference = 2 * M_PI * $radius;

    $offset = 0.0;
    $arcs = [];

    foreach ($segments as $segment) {
        $fraction = $total > 0 ? $segment['count'] / $total : 0;
        $length = $fraction * $circumference;

        $arcs[] = [
            'label' => $segment['label'],
            'color' => $segment['color'],
            'count' => $segment['count'],
            'percent' => $total > 0 ? round($fraction * 100) : 0,
            // A hair of spacing keeps adjacent arcs readable without a stroke.
            'dash' => max(0, $length - 1.5).' '.($circumference - max(0, $length - 1.5)),
            'rotation' => ($offset / $circumference) * 360,
        ];

        $offset += $length;
    }

    $counts = collect($arcs)->pluck('count')->implode(',');
@endphp

<div class="fi-section bg-white p-6 dark:bg-gray-900">
    {{-- Title block --}}
    <div class="mb-2">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $title }}</h3>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
    </div>

    @if ($total === 0)
        <p class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('filament/pages/dashboard.pipelines.empty') }}
        </p>
    @else
        <div x-data="{ shown: false, hovered: null, counts: [{{ $counts }}] }" x-init="$nextTick(() => shown = true)">
            {{-- Donut --}}
            <div class="relative mx-auto my-4 h-44 w-44">
                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" role="img"
                     aria-label="{{ $title }}: {{ $total }}">
                    <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke-width="13"
                            class="stroke-gray-100 dark:stroke-white/10" />

                    @foreach ($arcs as $i => $arc)
                        <a href="{{ $url }}" wire:navigate>
                            {{-- Hover state is bound through SVG presentation
                                 attributes, never :style. An Alpine :style binding
                                 REPLACES the static style attribute, which wiped the
                                 per-arc transform and stacked every segment at
                                 rotation 0. --}}
                            <circle
                                cx="60" cy="60" r="{{ $radius }}"
                                fill="none"
                                stroke="{{ $arc['color'] }}"
                                stroke-dasharray="{{ $arc['dash'] }}"
                                :stroke-dashoffset="shown ? 0 : {{ $circumference }}"
                                :stroke-width="hovered === {{ $i }} ? 16 : 13"
                                :opacity="hovered !== null && hovered !== {{ $i }} ? 0.35 : 1"
                                style="transform: rotate({{ $arc['rotation'] }}deg); transform-origin: 60px 60px; transition: stroke-dashoffset 800ms cubic-bezier(0.22,1,0.36,1) {{ $i * 70 }}ms, stroke-width 150ms ease, opacity 150ms ease;"
                                x-on:mouseenter="hovered = {{ $i }}"
                                x-on:mouseleave="hovered = null"
                                class="cursor-pointer"
                            >
                                <title>{{ $arc['label'] }}: {{ $arc['count'] }} ({{ $arc['percent'] }}%)</title>
                            </circle>
                        </a>
                    @endforeach
                </svg>

                {{-- Centre readout: the total, or the hovered slice --}}
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span
                        x-text="hovered === null ? {{ $total }} : counts[hovered]"
                        class="text-3xl font-semibold tabular-nums text-gray-950 dark:text-white"
                    ></span>
                    <span class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $centreLabel }}</span>
                </div>
            </div>

            {{-- Legend below, two columns, every stage listed including the empty ones --}}
            <ul class="grid grid-cols-2 gap-x-5 gap-y-1.5 border-t border-gray-100 pt-4 dark:border-white/10">
                @foreach ($all as $stage)
                    @php $arcIndex = collect($arcs)->search(fn (array $a): bool => $a['label'] === $stage['label']); @endphp

                    <li>
                        <a
                            href="{{ $url }}"
                            wire:navigate
                            @if ($arcIndex !== false)
                                x-on:mouseenter="hovered = {{ $arcIndex }}"
                                x-on:mouseleave="hovered = null"
                                :class="hovered === {{ $arcIndex }} ? 'bg-gray-50 dark:bg-white/5' : ''"
                            @endif
                            class="flex items-center gap-2 rounded-md px-1 py-0.5 transition"
                        >
                            <span
                                class="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                                style="background-color: {{ $stage['color'] }};{{ $stage['count'] === 0 ? ' opacity: 0.35;' : '' }}"
                            ></span>

                            <span @class([
                                'flex-1 truncate text-xs',
                                'text-gray-700 dark:text-gray-300' => $stage['count'] > 0,
                                'text-gray-400 dark:text-gray-500' => $stage['count'] === 0,
                            ])>{{ $stage['label'] }}</span>

                            <span @class([
                                'text-xs font-medium tabular-nums',
                                'text-gray-950 dark:text-white' => $stage['count'] > 0,
                                'text-gray-400 dark:text-gray-500' => $stage['count'] === 0,
                            ])>{{ $stage['count'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
