{{-- Brand mark pinned to the top-left of the topbar. --}}
<a
    href="{{ \App\Filament\Pages\Dashboard::getUrl() }}"
    wire:navigate
    class="fi-topbar-brand flex flex-shrink-0 items-center gap-2 ps-1"
    aria-label="{{ config('app.name') }}"
>
    <x-brand.qbitio-mark size="sm" class="text-gray-950 dark:text-white" />
</a>
