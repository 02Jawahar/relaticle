{{--
    Chat / Dashboard toggle for the home page.

    Deliberately mirrors the pipeline list/board switcher so the two read as the
    same control; this one flips a Livewire property instead of navigating.
--}}
@php
    $segmentClasses = 'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600';
    $activeClasses = 'bg-white text-primary-600 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/10 dark:text-primary-400 dark:ring-white/10';
    $inactiveClasses = 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200';
@endphp

<nav
    class="fi-view-switcher inline-flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 font-normal dark:bg-white/5"
    aria-label="{{ __('filament/pages/dashboard.switcher.label') }}"
>
    <button
        type="button"
        wire:click="setHomeView('chat')"
        @class([$segmentClasses, $activeClasses => ! $active, $inactiveClasses => $active])
        @if (! $active) aria-current="page" @endif
    >
        <x-filament::icon icon="heroicon-m-chat-bubble-left-right" class="h-4 w-4" />
        {{ __('filament/pages/dashboard.switcher.chat') }}
    </button>

    <button
        type="button"
        wire:click="setHomeView('dashboard')"
        @class([$segmentClasses, $activeClasses => $active, $inactiveClasses => ! $active])
        @if ($active) aria-current="page" @endif
    >
        <x-filament::icon icon="heroicon-m-chart-bar-square" class="h-4 w-4" />
        {{ __('filament/pages/dashboard.switcher.dashboard') }}
    </button>
</nav>
