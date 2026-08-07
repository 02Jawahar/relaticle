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
        'leads' => [
            'label' => __('filament/pages/dashboard.pipelines.leads'),
            'subtitle' => __('filament/pages/dashboard.pipelines.leads_subtitle'),
            'centre' => __('filament/pages/dashboard.pipelines.leads_centre'),
            'url' => \App\Filament\Resources\LeadResource::getUrl('board'),
        ],
        'deals' => [
            'label' => __('filament/pages/dashboard.pipelines.deals'),
            'subtitle' => __('filament/pages/dashboard.pipelines.deals_subtitle'),
            'centre' => __('filament/pages/dashboard.pipelines.deals_centre'),
            'url' => \App\Filament\Resources\DealResource::getUrl('board'),
        ],
        'orders' => [
            'label' => __('filament/pages/dashboard.pipelines.orders'),
            'subtitle' => __('filament/pages/dashboard.pipelines.orders_subtitle'),
            'centre' => __('filament/pages/dashboard.pipelines.orders_centre'),
            'url' => \App\Filament\Resources\OrderResource::getUrl('board'),
        ],
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

    {{-- Per-stage breakdown: one animated donut per pipeline, each slice linking
         through to that pipeline's board. --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @foreach ($pipelines as $key => $pipeline)
            @include('filament.app.pipeline-donut', [
                'title' => $pipeline['label'],
                'subtitle' => $pipeline['subtitle'],
                'centreLabel' => $pipeline['centre'],
                'url' => $pipeline['url'],
                'stages' => $breakdown[$key] ?? [],
            ])
        @endforeach
    </div>
</div>
