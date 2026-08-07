<?php

declare(strict_types=1);

use App\Enums\Pipeline\DealStage;
use App\Enums\Pipeline\LeadStage;
use App\Enums\Pipeline\OrderStage;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use App\Observers\Concerns\ConvertsOnWin;

mutates(ConvertsOnWin::class);

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
});

it('creates the deal as soon as a lead reaches Won, with no explicit convert call', function (): void {
    $lead = Lead::factory()->recycle([$this->user, $this->team])
        ->create(['stage' => LeadStage::NEGOTIATION]);

    expect(Deal::query()->where('lead_id', $lead->getKey())->exists())->toBeFalse();

    $lead->stage = LeadStage::WON;
    $lead->save();

    $deal = Deal::query()->where('lead_id', $lead->getKey())->first();

    expect($deal)->not->toBeNull()
        ->and($deal->stage)->toBe(DealStage::OPPORTUNITY)
        ->and($deal->name)->toBe($lead->name)
        ->and($deal->team_id)->toBe($lead->team_id);
});

it('raises the order as soon as a deal reaches Won', function (): void {
    $deal = Deal::factory()->recycle([$this->user, $this->team])
        ->create(['stage' => DealStage::PAYMENT]);

    $deal->stage = DealStage::WON;
    $deal->save();

    $order = Order::query()->where('deal_id', $deal->getKey())->first();

    expect($order)->not->toBeNull()
        ->and($order->stage)->toBe(OrderStage::ORDER_RECEIVED)
        ->and($order->name)->toBe($deal->name);
});

it('converts a lead created directly at Won, so imports and seeds flow too', function (): void {
    $lead = Lead::factory()->recycle([$this->user, $this->team])
        ->create(['stage' => LeadStage::WON]);

    expect(Deal::query()->where('lead_id', $lead->getKey())->count())->toBe(1);
});

it('does not convert twice when a won record is saved again', function (): void {
    $lead = Lead::factory()->recycle([$this->user, $this->team])
        ->create(['stage' => LeadStage::WON]);

    $lead->save();
    $lead->touch();

    expect(Deal::query()->where('lead_id', $lead->getKey())->count())->toBe(1);
});

it('leaves records alone until they actually reach Won', function (): void {
    $lead = Lead::factory()->recycle([$this->user, $this->team])
        ->create(['stage' => LeadStage::NEW]);

    foreach ([LeadStage::CONTACTED, LeadStage::QUALIFIED, LeadStage::PROPOSAL] as $stage) {
        $lead->stage = $stage;
        $lead->save();
    }

    expect(Deal::query()->where('lead_id', $lead->getKey())->exists())->toBeFalse();

    // Lost is terminal but not a win, so it must not hand anything downstream.
    $lead->stage = LeadStage::LOST;
    $lead->save();

    expect(Deal::query()->where('lead_id', $lead->getKey())->exists())->toBeFalse();
});

it('walks the whole funnel from a new lead to an order on stage changes alone', function (): void {
    $lead = Lead::factory()->recycle([$this->user, $this->team])
        ->create(['stage' => LeadStage::NEW]);

    $lead->stage = LeadStage::WON;
    $lead->save();

    $deal = Deal::query()->where('lead_id', $lead->getKey())->firstOrFail();

    $deal->stage = DealStage::WON;
    $deal->save();

    $order = Order::query()->where('deal_id', $deal->getKey())->firstOrFail();

    expect($order->deal->lead_id)->toBe($lead->getKey())
        ->and($lead->fresh()->stage)->toBe(LeadStage::WON)
        ->and($deal->fresh()->stage)->toBe(DealStage::WON);
});
