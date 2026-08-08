{{--
    The brand card, fixed to the top-left corner of the shell.

    Rendered at the body root, not inside the sidebar: the sidebar has a
    backdrop-filter, which makes it the containing block for fixed descendants,
    so a card placed there was positioned against the sidebar rather than the
    viewport.

    BODY_START also fires on the auth pages, which have no shell and no tenant —
    and Dashboard::getUrl() cannot resolve a tenant route parameter there. Those
    pages show the brand through the panel's own brandLogo, so the card is simply
    absent, and the tenant is passed explicitly rather than resolved ambiently.

    Being outside the sidebar puts it beyond the reach of `fi-sidebar-open`, so
    the expanded state comes from the Alpine store the sidebar itself writes.
--}}
@php
    $tenant = \Filament\Facades\Filament::getTenant();
@endphp

@if ($tenant)
    <a
        href="{{ \App\Filament\Pages\Dashboard::getUrl(tenant: $tenant) }}"
        wire:navigate
        x-data="{}"
        x-bind:class="{ 'fi-brand-open': $store.sidebar.isOpen }"
        class="fi-sidebar-brand"
        aria-label="{{ config('app.name') }}"
    >
        <x-brand.qbitio-mark size="md" />

        <span x-show="$store.sidebar.isOpen" x-cloak class="fi-sidebar-brand-name">
            Qbitio
        </span>
    </a>
@endif
