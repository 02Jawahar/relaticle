@php
    /** @var \App\Models\Lead|\App\Models\Deal|\App\Models\Order $record */
    $record = $getRecord();
    $stage = $record->stage;
    $current = $record->sub_stage?->value;
    $subStages = $stage->subStages();
@endphp

{{-- color-scheme makes the browser render the native option popup in the page's
     theme (a dark popup in dark mode instead of the default white one), and the
     explicit per-option colours cover Windows/Chromium, which otherwise paints
     the open list white regardless. --}}
<select
    wire:key="sub-stage-select-{{ $record->getKey() }}-{{ $current }}"
    @disabled($subStages === [])
    x-on:change="$wire.setSubStage(@js($record->getKey()), $event.target.value === '' ? null : $event.target.value)"
    class="block w-full rounded-lg bg-white py-2 pl-3 pr-8 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition [color-scheme:light] focus:outline-none focus:ring-2 focus:ring-primary-600 disabled:pointer-events-none disabled:opacity-70 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:[color-scheme:dark] dark:focus:ring-primary-500"
>
    <option value="" @selected($current === null) class="bg-white text-gray-950 dark:bg-gray-900 dark:text-white">
        {{ __('pipelines.fields.sub_stage.unset') }}
    </option>

    @foreach ($subStages as $subStage)
        <option value="{{ $subStage->value }}" @selected($current === $subStage->value) class="bg-white text-gray-950 dark:bg-gray-900 dark:text-white">
            {{ $subStage->getLabel() }}
        </option>
    @endforeach
</select>
