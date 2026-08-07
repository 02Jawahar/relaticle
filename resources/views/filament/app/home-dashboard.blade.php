{{--
    The dashboard half of the home page.

    Laid out as a dense grid so the viewport is used rather than leaving a band
    of empty page below the charts. Stage colours come from the pipeline enums,
    so every widget stays in step with the kanban columns.
--}}
@php
    use App\Filament\Resources\DealResource;
    use App\Filament\Resources\LeadResource;
    use App\Filament\Resources\OrderResource;
    use Illuminate\Support\Number;

    $stats = $this->pipelineStats;
    $breakdown = $this->pipelineBreakdown;
    $funnel = $this->conversionFunnel;
    $companies = $this->topCompanies;
    $activity = $this->recentActivity;
    $tasks = $this->myTasks;

    $cards = [
        ['label' => __('filament/pages/dashboard.stats.leads_open'), 'value' => number_format($stats['leads_open']), 'icon' => 'heroicon-o-funnel', 'url' => LeadResource::getUrl('board')],
        ['label' => __('filament/pages/dashboard.stats.deals_open'), 'value' => number_format($stats['deals_open']), 'icon' => 'heroicon-o-trophy', 'url' => DealResource::getUrl('board')],
        ['label' => __('filament/pages/dashboard.stats.pipeline_value'), 'value' => Number::currency($stats['pipeline_value']), 'icon' => 'heroicon-o-banknotes', 'url' => DealResource::getUrl('index')],
        ['label' => __('filament/pages/dashboard.stats.orders_active'), 'value' => number_format($stats['orders_active']), 'icon' => 'heroicon-o-cube', 'url' => OrderResource::getUrl('board')],
    ];

    $pipelines = [
        'leads' => ['label' => __('filament/pages/dashboard.pipelines.leads'), 'subtitle' => __('filament/pages/dashboard.pipelines.leads_subtitle'), 'centre' => __('filament/pages/dashboard.pipelines.leads_centre'), 'url' => LeadResource::getUrl('board')],
        'deals' => ['label' => __('filament/pages/dashboard.pipelines.deals'), 'subtitle' => __('filament/pages/dashboard.pipelines.deals_subtitle'), 'centre' => __('filament/pages/dashboard.pipelines.deals_centre'), 'url' => DealResource::getUrl('board')],
        'orders' => ['label' => __('filament/pages/dashboard.pipelines.orders'), 'subtitle' => __('filament/pages/dashboard.pipelines.orders_subtitle'), 'centre' => __('filament/pages/dashboard.pipelines.orders_centre'), 'url' => OrderResource::getUrl('board')],
    ];
@endphp

<div class="mt-6 space-y-4">
    {{-- Headline numbers --}}
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ($cards as $card)
            <a
                href="{{ $card['url'] }}"
                wire:navigate
                class="fi-section flex flex-col gap-2 bg-white p-4 transition hover:-translate-y-0.5 dark:bg-gray-900"
            >
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <x-filament::icon :icon="$card['icon']" class="h-4 w-4" />
                    <span class="truncate text-[11px] font-medium uppercase tracking-wide">{{ $card['label'] }}</span>
                </div>

                <div class="text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $card['value'] }}</div>
            </a>
        @endforeach
    </div>

    {{-- Funnel + my tasks + recent activity --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Funnel --}}
        <div class="fi-section bg-white p-5 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('filament/pages/dashboard.funnel.heading') }}</h3>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('filament/pages/dashboard.funnel.subtitle') }}</p>

            <ul class="mt-5 space-y-4">
                @foreach ($funnel as $step)
                    <li>
                        <a href="{{ $step['url'] }}" wire:navigate class="group block">
                            <div class="mb-1.5 flex items-baseline justify-between gap-2">
                                <span class="text-sm font-medium text-gray-700 group-hover:underline dark:text-gray-300">{{ $step['label'] }}</span>
                                <span class="flex items-baseline gap-2">
                                    <span class="text-sm font-semibold tabular-nums text-gray-950 dark:text-white">{{ number_format($step['count']) }}</span>
                                    @if ($step['rate'] !== null)
                                        <span class="text-[11px] tabular-nums text-gray-400 dark:text-gray-500">
                                            {{ $step['rate'] }}% {{ __('filament/pages/dashboard.funnel.of_previous') }}
                                        </span>
                                    @endif
                                </span>
                            </div>

                            <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                <div
                                    class="h-full rounded-full transition-all duration-700"
                                    style="width: {{ $step['total'] > 0 ? max(3, (int) round($step['count'] / $step['total'] * 100)) : 3 }}%; background-color: {{ $step['color'] }};"
                                ></div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- My tasks --}}
        <div class="fi-section flex flex-col bg-white p-5 dark:bg-gray-900">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('filament/pages/dashboard.my_tasks.heading') }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('filament/pages/dashboard.my_tasks.subtitle') }}</p>
                </div>
                <a href="{{ $this->getTasksIndexUrl() }}" wire:navigate class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-400">
                    {{ __('filament/pages/dashboard.my_tasks.view_all') }}
                </a>
            </div>

            @if ($tasks->isEmpty())
                <p class="flex flex-1 items-center justify-center py-8 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('filament/pages/dashboard.my_tasks.empty') }}
                </p>
            @else
                <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($tasks->take(6) as $task)
                        <li class="py-2">
                            <a href="{{ $task->editUrl }}" wire:navigate class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 flex-shrink-0 text-gray-400" />
                                <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-300">{{ $task->title }}</span>
                                @if ($task->dueAt)
                                    <span @class([
                                        'flex-shrink-0 rounded-md px-1.5 py-0.5 text-[11px] font-medium tabular-nums',
                                        'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' => $task->dueAt->isPast(),
                                        'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400' => ! $task->dueAt->isPast(),
                                    ])>{{ $task->dueAt->format('M j') }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Recent activity --}}
        <div class="fi-section flex flex-col bg-white p-5 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('filament/pages/dashboard.activity.heading') }}</h3>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('filament/pages/dashboard.activity.subtitle') }}</p>

            @if ($activity === [])
                <p class="flex flex-1 items-center justify-center py-8 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('filament/pages/dashboard.activity.empty') }}
                </p>
            @else
                <ul class="mt-4 space-y-2.5">
                    @foreach ($activity as $entry)
                        <li class="flex items-start gap-2">
                            <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                            <span class="min-w-0 flex-1 text-xs leading-snug text-gray-600 dark:text-gray-400">
                                <span class="font-medium text-gray-900 dark:text-gray-200">{{ $entry['causer'] }}</span>
                                {{ $entry['description'] }}
                                <span class="text-gray-400 dark:text-gray-500">{{ $entry['subject'] }}</span>
                            </span>
                            <span class="flex-shrink-0 text-[11px] tabular-nums text-gray-400 dark:text-gray-500">{{ $entry['when'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Stage donuts --}}
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

    {{-- Top companies --}}
    <div class="fi-section bg-white p-5 dark:bg-gray-900">
        <div class="mb-4 flex items-start justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('filament/pages/dashboard.companies.heading') }}</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('filament/pages/dashboard.companies.subtitle') }}</p>
            </div>
            <a href="{{ DealResource::getUrl('index') }}" wire:navigate class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-400">
                {{ __('filament/pages/dashboard.pipelines.open_board') }}
            </a>
        </div>

        @if ($companies === [])
            <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('filament/pages/dashboard.companies.empty') }}</p>
        @else
            @php $widest = max(1, ...array_map(fn (array $c): float => (float) $c['total_amount'], $companies)); @endphp

            <ul class="space-y-3">
                @foreach ($companies as $company)
                    <li class="flex items-center gap-3">
                        <span class="w-40 flex-shrink-0 truncate text-sm text-gray-700 dark:text-gray-300">{{ $company['label'] }}</span>

                        <span class="flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <span
                                class="block h-2 rounded-full bg-primary-500 transition-all duration-700"
                                style="width: {{ max(3, (int) round((float) $company['total_amount'] / $widest * 100)) }}%;"
                            ></span>
                        </span>

                        <span class="w-16 flex-shrink-0 text-end text-[11px] tabular-nums text-gray-400 dark:text-gray-500">
                            {{ __('filament/pages/dashboard.companies.deals_count', ['count' => $company['count']]) }}
                        </span>

                        <span class="w-24 flex-shrink-0 text-end text-sm font-semibold tabular-nums text-gray-950 dark:text-white">
                            {{ Number::currency((float) $company['total_amount']) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
