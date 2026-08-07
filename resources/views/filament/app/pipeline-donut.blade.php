{{--
    An animated donut for one pipeline's stage distribution.

    Plain inline SVG rather than a charting library: no extra bundle, and it works
    under the app's CSP. Segments are drawn as arcs on one circle using
    stroke-dasharray, and each is a link to the pipeline's board so clicking a
    slice lands on the records it represents.

    Expects: $title, $url, $stages (list of ['label','color','count']).
--}}
@php
    $segments = collect($stages)->filter(fn (array $s): bool => $s['count'] > 0)->values();
    $total = (int) $segments->sum('count');

    // Circumference of r=42 in a 120x120 box, so dasharray maths stays readable.
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
            // A 1px gap keeps adjacent arcs distinguishable without a border.
            'dash' => max(0, $length - 1).' '.($circumference - max(0, $length - 1)),
            'rotation' => ($offset / $circumference) * 360,
        ];

        $offset += $length;
    }
@endphp

<div class="fi-section bg-white p-5 dark:bg-gray-900">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $title }}</h3>
        <a href="{{ $url }}" wire:navigate class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-400">
            {{ __('filament/pages/dashboard.pipelines.open_board') }}
        </a>
    </div>

    @if ($total === 0)
        <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('filament/pages/dashboard.pipelines.empty') }}
        </p>
    @else
        <div
            x-data="{ shown: false, hovered: null }"
            x-init="$nextTick(() => shown = true)"
            class="flex items-center gap-5"
        >
            {{-- Donut --}}
            <div class="relative flex-shrink-0">
                <svg viewBox="0 0 120 120" class="h-32 w-32 -rotate-90" role="img"
                     aria-label="{{ $title }}: {{ $total }} {{ __('filament/pages/dashboard.pipelines.records') }}">
                    <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke-width="14"
                            class="stroke-gray-100 dark:stroke-white/10" />

                    @foreach ($arcs as $i => $arc)
                        <a href="{{ $url }}" wire:navigate>
                            <circle
                                cx="60" cy="60" r="{{ $radius }}"
                                fill="none"
                                stroke="{{ $arc['color'] }}"
                                stroke-width="14"
                                stroke-linecap="butt"
                                stroke-dasharray="{{ $arc['dash'] }}"
                                {{-- Grow from nothing on load: the dash offset animates to 0 --}}
                                :stroke-dashoffset="shown ? 0 : {{ $circumference }}"
                                style="transform: rotate({{ $arc['rotation'] }}deg); transform-origin: 60px 60px; transition: stroke-dashoffset 700ms cubic-bezier(0.22,1,0.36,1) {{ $i * 60 }}ms, stroke-width 150ms ease;"
                                :style="hovered === {{ $i }} ? 'stroke-width: 17' : ''"
                                x-on:mouseenter="hovered = {{ $i }}"
                                x-on:mouseleave="hovered = null"
                                class="cursor-pointer"
                            >
                                <title>{{ $arc['label'] }}: {{ $arc['count'] }} ({{ $arc['percent'] }}%)</title>
                            </circle>
                        </a>
                    @endforeach
                </svg>

                {{-- Total in the hole --}}
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span
                        x-text="hovered === null ? '{{ $total }}' : ['{{ collect($arcs)->pluck('count')->implode("','") }}'][hovered]"
                        class="text-xl font-semibold tabular-nums text-gray-950 dark:text-white"
                    ></span>
                    <span class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('filament/pages/dashboard.pipelines.records') }}
                    </span>
                </div>
            </div>

            {{-- Legend --}}
            <ul class="min-w-0 flex-1 space-y-1">
                @foreach ($arcs as $i => $arc)
                    <li>
                        <a
                            href="{{ $url }}"
                            wire:navigate
                            x-on:mouseenter="hovered = {{ $i }}"
                            x-on:mouseleave="hovered = null"
                            :class="hovered === {{ $i }} ? 'bg-gray-50 dark:bg-white/5' : ''"
                            class="flex items-center gap-2 rounded-md px-1.5 py-0.5 transition"
                        >
                            <span class="h-2 w-2 flex-shrink-0 rounded-full" style="background-color: {{ $arc['color'] }};"></span>
                            <span class="flex-1 truncate text-xs text-gray-700 dark:text-gray-300">{{ $arc['label'] }}</span>
                            <span class="text-xs font-medium tabular-nums text-gray-950 dark:text-white">{{ $arc['count'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
