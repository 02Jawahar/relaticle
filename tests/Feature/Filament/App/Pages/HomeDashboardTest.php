<?php

declare(strict_types=1);

use App\Actions\Deal\CreateDeal;
use App\Actions\Order\CreateOrder;
use App\Enums\Pipeline\DealStage;
use App\Enums\Pipeline\LeadStage;
use App\Enums\Pipeline\OrderStage;
use App\Filament\Pages\Dashboard;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Livewire\Livewire;
use Relaticle\CustomFields\Services\TenantContextService;

mutates(Dashboard::class);

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
    Filament::setTenant($this->user->currentTeam);
});

it('sums both deal amounts and order values into the pipeline value', function (): void {
    $team = $this->user->currentTeam;
    TenantContextService::setTenantId($team->getKey());

    try {
        resolve(CreateDeal::class)->execute($this->user, [
            'name' => 'Pipeline deal',
            'custom_fields' => ['amount' => 100000],
        ]);
        resolve(CreateOrder::class)->execute($this->user, [
            'name' => 'Pipeline order',
            'custom_fields' => ['order_value' => 50000],
        ]);

        $dashboard = Livewire::test(Dashboard::class)->instance();

        expect($dashboard->pipelineStats()['pipeline_value'])->toBe(150000.0);
    } finally {
        TenantContextService::setTenantId(null);
    }
});

it('excludes soft-deleted records from the dashboard counts', function (): void {
    $team = $this->user->currentTeam;

    Deal::factory()->recycle([$this->user, $team])->create(['stage' => DealStage::OPPORTUNITY]);
    $trashed = Deal::factory()->recycle([$this->user, $team])->create(['stage' => DealStage::OPPORTUNITY]);
    $trashed->delete();

    $dashboard = Livewire::test(Dashboard::class)->instance();

    $opportunity = collect($dashboard->pipelineBreakdown()['deals'])
        ->firstWhere('label', DealStage::OPPORTUNITY->getLabel());

    // Only the kept deal is counted, not the trashed one.
    expect($opportunity['count'])->toBe(1)
        ->and($dashboard->pipelineStats()['deals_open'])->toBe(1)
        ->and($dashboard->conversionFunnel()[1]['count'])->toBe(1);
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

it('reports counts from the CRM records rather than fixed figures', function (): void {
    // Open records, plus one of each in a terminal stage that must be excluded
    // from the "open" counts but still appear in the funnel totals.
    Lead::factory()->count(3)->recycle([$this->user, $this->user->currentTeam])
        ->create(['stage' => LeadStage::NEW]);
    Lead::factory()->recycle([$this->user, $this->user->currentTeam])
        ->create(['stage' => LeadStage::LOST]);

    Deal::factory()->count(2)->recycle([$this->user, $this->user->currentTeam])
        ->create(['stage' => DealStage::OPPORTUNITY]);
    Deal::factory()->recycle([$this->user, $this->user->currentTeam])
        ->create(['stage' => DealStage::LOST]);

    Order::factory()->count(2)->recycle([$this->user, $this->user->currentTeam])
        ->create(['stage' => OrderStage::PRODUCTION]);
    Order::factory()->recycle([$this->user, $this->user->currentTeam])
        ->create(['stage' => OrderStage::CLOSED]);

    $page = Livewire::withQueryParams(['view' => Dashboard::VIEW_DASHBOARD])
        ->test(Dashboard::class)
        ->instance();

    $stats = $page->pipelineStats;
    $funnel = collect($page->conversionFunnel)->keyBy('label');

    expect($stats['leads_open'])->toBe(3)
        ->and($stats['deals_open'])->toBe(2)
        ->and($stats['orders_active'])->toBe(2)
        // The funnel counts everything, including the terminal stages.
        ->and($funnel['Leads']['count'])->toBe(4)
        ->and($funnel['Deals']['count'])->toBe(3)
        ->and($funnel['Orders']['count'])->toBe(3);
});

it('counts only the current team\'s records', function (): void {
    Lead::factory()->count(2)->recycle([$this->user, $this->user->currentTeam])
        ->create(['stage' => LeadStage::NEW]);

    $outsider = User::factory()->withPersonalTeam()->create();
    Lead::factory()->count(5)->for($outsider->currentTeam)->create([
        'creator_id' => $outsider->getKey(),
        'stage' => LeadStage::NEW,
    ]);

    $page = Livewire::test(Dashboard::class)->instance();

    expect($page->pipelineStats['leads_open'])->toBe(2);
});

it('shows each stage of every pipeline in the breakdown', function (): void {
    Lead::factory()->recycle([$this->user, $this->user->currentTeam])
        ->create(['stage' => LeadStage::QUALIFIED]);

    $breakdown = Livewire::test(Dashboard::class)->instance()->pipelineBreakdown;

    $qualified = collect($breakdown['leads'])->firstWhere('label', LeadStage::QUALIFIED->getLabel());

    expect($breakdown)->toHaveKeys(['leads', 'deals', 'orders'])
        ->and($breakdown['leads'])->toHaveCount(count(LeadStage::cases()))
        ->and($qualified['count'])->toBe(1);
});
