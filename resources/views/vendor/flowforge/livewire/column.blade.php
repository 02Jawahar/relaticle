{{--
    Override of vendor/relaticle/flowforge/resources/views/livewire/column.blade.php.

    Changes from upstream, all presentational — the drag-and-drop, lazy-scroll and
    action wiring are untouched:
      * the column body carries a soft pastel wash of its stage colour instead of
        sitting white on grey, and the hard header rule is dropped
      * a colour dot precedes the label, so a stage is identifiable at a glance
      * softer radius, a low-contrast border and a barely-there shadow

    Column colours arrive as hex from the stage enums, so the tint is derived with
    colour-mix() rather than Filament's shade variables.
--}}
@props(['columnId', 'column', 'config'])

@php
    use Relaticle\Flowforge\Support\ColorResolver;

    $resolvedColor = ColorResolver::resolve($column['color']);
    $isSemantic = ColorResolver::isSemantic($resolvedColor);
    $colorShades = $isSemantic ? null : $resolvedColor;

    // The raw value the board handed us; a hex string for the pipeline boards.
    $accent = is_string($column['color'] ?? null) && str_starts_with($column['color'], '#')
        ? $column['color']
        : null;
@endphp

<div
    @if ($accent)
        style="--ff-accent: {{ $accent }};"
    @endif
    class="flowforge-column fi-pipeline-column w-[320px] min-w-[320px] flex-shrink-0 flex flex-col max-h-full overflow-hidden"
>
    <!-- Column Header -->
    <div class="flowforge-column-header flex items-center justify-between py-3 px-4">
        <div class="flex items-center">
            @if ($accent)
                <span class="fi-pipeline-column-dot" aria-hidden="true"></span>
            @endif

            @if ($column['icon'] ?? null)
                <x-filament::icon :icon="$column['icon']" class="h-4 w-4 text-gray-500 dark:text-gray-400 me-2" />
            @endif

            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ $column['label'] }}
            </h3>

            {{-- Count Badge --}}
            @if($isSemantic)
                <x-filament::badge
                    tag="div"
                    :color="$resolvedColor"
                    class="ms-2"
                >
                    {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
                </x-filament::badge>
            @elseif($colorShades)
                <div
                    @style([
                        Filament\Support\get_color_css_variables($resolvedColor, shades: [50, 300, 600, 700])
                    ])
                    @class([
                        'ms-2 items-center px-2 py-0.5 rounded-md text-xs font-semibold',
                        'bg-custom-50 dark:bg-custom-600/20',
                        'text-custom-700 dark:text-custom-300',
                    ])>
                    {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
                </div>
            @else
                <div class="ms-2 items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-50 dark:bg-gray-600/20 text-gray-700 dark:text-gray-300">
                    {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
                </div>
            @endif
        </div>

        @php
            $processedActions = $this->getBoardColumnActions($columnId);
        @endphp

        @if(count($processedActions) > 0)
            <div>
                @if(count($processedActions) === 1)
                    {{ $processedActions[0] }}
                @else
                    <x-filament-actions::group :actions="$processedActions"/>
                @endif
            </div>
        @endif
    </div>

    <!-- Column Content -->
    <div
        data-column-id="{{ $columnId }}"
        @if($this->getBoard()->getPositionIdentifierAttribute())
            x-sortable
        x-sortable-group="cards"
        @end.stop="handleSortableEnd($event)"
        @endif
        @if(isset($column['total']) && $column['total'] > count($column['items']))
            @scroll.throttle.100ms="handleColumnScroll($event, '{{ $columnId }}')"
        @endif
        class="flowforge-column-content px-3 pt-3 pb-3 flex-1 overflow-y-auto overflow-x-hidden overscroll-y-contain kanban-cards"
        style="max-height: calc(100vh - 12rem);"
    >
        @if (isset($column['items']) && count($column['items']) > 0)
            @foreach ($column['items'] as $record)
                <x-flowforge::card
                    :record="$record"
                    :config="$config"
                    :columnId="$columnId"
                    wire:key="card-{{ $record['id'] }}"
                />
            @endforeach

            <div class="py-3 text-center">
                @if(isset($column['total']) && $column['total'] > count($column['items']))
                    <div
                        x-intersect.margin.300px="handleSmoothScroll('{{ $columnId }}')"
                        class="w-full">

                        <div x-show="isLoadingColumn('{{ $columnId }}')"
                             x-transition
                             class="text-xs text-primary-600 dark:text-primary-400 flex items-center justify-center gap-2">
                            {{ __('flowforge::flowforge.loading_more_cards') }}
                        </div>
                    </div>
                @endif
            </div>
        @else
            <x-flowforge::empty-column
                :columnId="$columnId"
                :pluralCardLabel="$config['pluralCardLabel']"
            />
        @endif
    </div>
</div>
