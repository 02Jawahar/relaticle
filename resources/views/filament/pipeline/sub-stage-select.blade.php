@php
    /** @var \App\Models\Lead|\App\Models\Deal|\App\Models\Order $record */
    $record = $getRecord();
    $stage = $record->stage;
    $current = $record->sub_stage?->value;
    $subStages = $stage->subStages();
@endphp

<select
    wire:key="sub-stage-select-{{ $record->getKey() }}-{{ $current }}"
    @disabled($subStages === [])
    x-on:change="$wire.setSubStage(@js($record->getKey()), $event.target.value === '' ? null : $event.target.value)"
    class="block w-full rounded-lg bg-white py-2 pl-3 pr-8 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition focus:outline-none focus:ring-2 focus:ring-primary-600 disabled:pointer-events-none disabled:opacity-70 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:focus:ring-primary-500"
>
    <option value="" @selected($current === null)>{{ __('pipelines.fields.sub_stage.placeholder') }}</option>

    @foreach ($subStages as $subStage)
        <option value="{{ $subStage->value }}" @selected($current === $subStage->value)>
            {{ $subStage->getLabel() }}
        </option>
    @endforeach
</select>
