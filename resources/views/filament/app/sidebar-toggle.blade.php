{{--
    The sidebar's expand/collapse control.

    Filament only renders this pair inside the sidebar when the panel has no
    topbar (see vendor/filament/filament/resources/views/livewire/sidebar.blade.php,
    both buttons are guarded by `! $hasTopbar`) and puts them in the topbar
    otherwise. This panel has a topbar, so the control is rendered here through
    SIDEBAR_START and the topbar pair is hidden in the theme.

    Two buttons rather than one with a bound icon, mirroring Filament: each is
    driven by `$store.sidebar.isOpen`, so no icon swap is needed and the state
    stays in the one place that persists it.
--}}
<div class="fi-sidebar-toggle-ctn" x-data="{}">
    <x-filament::icon-button
        color="gray"
        icon="heroicon-o-chevron-double-right"
        icon-size="md"
        :label="__('filament-panels::layout.actions.sidebar.expand.label')"
        aria-controls="fi-main-sidebar"
        x-bind:aria-expanded="$store.sidebar.isOpen"
        x-on:click="$store.sidebar.open()"
        x-show="! $store.sidebar.isOpen"
        x-cloak
        class="fi-sidebar-toggle-btn"
    />

    <x-filament::icon-button
        color="gray"
        icon="heroicon-o-chevron-double-left"
        icon-size="md"
        :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
        aria-controls="fi-main-sidebar"
        x-bind:aria-expanded="$store.sidebar.isOpen"
        x-on:click="$store.sidebar.close()"
        x-show="$store.sidebar.isOpen"
        x-cloak
        class="fi-sidebar-toggle-btn"
    />
</div>
