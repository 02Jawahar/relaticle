{{--
    The brand, in the top-left corner of the shell.

    Previously in the topbar, which starts to the right of the sidebar and so left
    the corner above the rail empty. Rendering it here fills that space and gives
    the mark a region of its own rather than competing with the search field.

    The wordmark appears only when the sidebar is expanded; the rail has no room
    for it. `$store.sidebar` is in scope because the sidebar root carries x-data.
--}}
<a
    href="{{ \App\Filament\Pages\Dashboard::getUrl() }}"
    wire:navigate
    class="fi-sidebar-brand"
    aria-label="{{ config('app.name') }}"
>
    <x-brand.qbitio-mark size="lg" />

    <span x-show="$store.sidebar.isOpen" x-cloak class="fi-sidebar-brand-name">
        Qbitio
    </span>
</a>
