{{--
    The dashboard half of the home page: headline numbers plus a per-stage
    breakdown of each pipeline. Stage colours come from the pipeline enums, so
    the bars stay in step with the kanban columns.
--}}
@php
    $stats = $this->pipelineStats;
    $breakdown = $this->pipelineBreakdown;

    $cards = [
        [
            'label' => __('filament/pages/dashboard.stats.leads_open'),
            'value' => number_format($stats['leads_open']),
            'icon' => 'heroicon-o-funnel',
            'url' => \App\Filament\Resources\LeadResource::getUrl('board'),
        ],
        [
            'label' => __('filament/pages/dashboard.stats.deals_open'),
            'value' => number_format($stats['deals_open']),
            'icon' => 'heroicon-o-trophy',
            'url' => \App\Filament\Resources\DealResource::getUrl('board'),
        ],
        [
            'label' => __('filament/pages/dashboard.stats.pipeline_value'),
            'value' => \Illuminate\Support\Number::currency($stats['pipeline_value']),
            'icon' => 'heroicon-o-banknotes',
            'url' => \App\Filament\Resources\DealResource::getUrl('index'),
        ],
        [
            'label' => __('filament/pages/dashboard.stats.orders_active'),
            'value' => number_format($stats['orders_active']),
            'icon' => 'heroicon-o-cube',
            'url' => \App\Filament\Resources\OrderResource::getUrl('board'),
        ],
    ];

    $pipelines = [
        'leads' => ['label' => __('filament/pages/dashboard.pipelines.leads'), 'url' => \App\Filament\Resources\LeadResource::getUrl('board')],
        'deals' => ['label' => __('filament/pages/dashboard.pipelines.deals'), 'url' => \App\Filament\Resources\DealResource::getUrl('board')],
        'orders' => ['label' => __('filament/pages/dashboard.pipelines.orders'), 'url' => \App\Filament\Resources\OrderResource::getUrl('board')],
    ];
@endphp

<div class="mt-10 space-y-6">
    {{-- Headline numbers --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <a
                href="{{ $card['url'] }}"
                wire:navigate
                class="fi-section group flex flex-col gap-3 bg-white p-5 transition hover:-translate-y-0.5 dark:bg-gray-900"
            >
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <x-filament::icon :icon="$card['icon']" class="h-4 w-4" />
                    <span class="text-xs font-medium uppercase tracking-wide">{{ $card['label'] }}</span>
                </div>

                <div class="text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">
                    {{ $card['value'] }}
                </div>
            </a>
        @endforeach
    </div>

    {{-- Per-stage breakdown --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @foreach ($pipelines as $key => $pipeline)
            @php
                $stages = $breakdown[$key] ?? [];
                $total = collect($stages)->sum('count');
            @endphp

            <div class="fi-section bg-white p-5 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $pipeline['label'] }}</h3>
                    <a
                        href="{{ $pipeline['url'] }}"
                        wire:navigate
                        class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                    >
                        {{ __('filament/pages/dashboard.pipelines.open_board') }}
                    </a>
                </div>

                @if ($total === 0)
                    <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('filament/pages/dashboard.pipelines.empty') }}
                    </p>
                @else
                    <ul class="space-y-2.5">
                        @foreach ($stages as $stage)
                            @continue($stage['count'] === 0)

                            <li class="flex items-center gap-3">
                                <span
                                    class="h-2 w-2 flex-shrink-0 rounded-full"
                                    style="background-color: {{ $stage['color'] }};"
                                    aria-hidden="true"
                                ></span>

                                <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-300">
                                    {{ $stage['label'] }}
                                </span>

                                <span class="w-24 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10" aria-hidden="true">
                                    <span
                                        class="block h-1.5 rounded-full"
                                        style="width: {{ max(4, (int) round($stage['count'] / $total * 100)) }}%; background-color: {{ $stage['color'] }};"
                                    ></span>
                                </span>

                                <span class="w-8 text-end text-sm font-medium tabular-nums text-gray-950 dark:text-white">
                                    {{ $stage['count'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</div>
