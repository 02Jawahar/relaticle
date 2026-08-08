<?php

declare(strict_types=1);

use App\Filament\Pages\Dashboard;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Livewire\Livewire;

mutates(Dashboard::class);

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
    Filament::setTenant($this->user->currentTeam);
});

it('gives the dashboard view the full content width', function (): void {
    $page = Livewire::withQueryParams(['view' => Dashboard::VIEW_DASHBOARD])
        ->test(Dashboard::class)
        ->assertSet('homeView', Dashboard::VIEW_DASHBOARD)
        ->instance();

    expect($page->getMaxContentWidth())->toBe(Width::Full);
});

// The width cannot depend on the active view: it lands on <main>, in the layout,
// which a Livewire view switch does not re-render. The chat half keeps its own
// readable line length instead, so full width costs it nothing.
it('keeps the full content width on the chat view too', function (): void {
    $page = Livewire::test(Dashboard::class)
        ->assertSet('homeView', Dashboard::VIEW_CHAT)
        ->instance();

    expect($page->getMaxContentWidth())->toBe(Width::Full);
});

it('constrains the chat column itself rather than relying on the page width', function (): void {
    Livewire::test(Dashboard::class)
        ->assertSet('homeView', Dashboard::VIEW_CHAT)
        ->assertSee('max-w-3xl', escape: false);
});

it('stays full width after switching view without a page reload', function (): void {
    $component = Livewire::test(Dashboard::class)->call('setHomeView', Dashboard::VIEW_DASHBOARD);

    expect($component->instance()->getMaxContentWidth())->toBe(Width::Full);
});

it('renders the dashboard widgets when the dashboard view is selected', function (): void {
    Livewire::withQueryParams(['view' => Dashboard::VIEW_DASHBOARD])
        ->test(Dashboard::class)
        ->assertSee('Pipeline funnel')
        ->assertSee('Recent activity')
        ->assertSee('Lead Stages');
});

it('renders the chat composer instead of the widgets on the chat view', function (): void {
    Livewire::test(Dashboard::class)
        ->assertSet('homeView', Dashboard::VIEW_CHAT)
        ->assertDontSee('Pipeline funnel');
});
