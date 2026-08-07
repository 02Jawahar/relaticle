<?php

declare(strict_types=1);

use App\Actions\Deal\ConvertDealToOrder;
use App\Actions\Lead\ConvertLeadToDeal;
use App\Enums\Pipeline\DealStage;
use App\Enums\Pipeline\DealSubStage;
use App\Enums\Pipeline\LeadStage;
use App\Enums\Pipeline\LeadSubStage;
use App\Enums\Pipeline\OrderStage;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\People;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

mutates(ConvertLeadToDeal::class, ConvertDealToOrder::class);

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
});

it('converts a lead into a deal, carrying company and contact across', function (): void {
    $company = Company::factory()->recycle([$this->user, $this->team])->create();
    $contact = People::factory()->recycle([$this->user, $this->team])->create();

    $lead = Lead::factory()->recycle([$this->user, $this->team])->create([
        'name' => 'Acme robotics line',
        'company_id' => $company->getKey(),
        'contact_id' => $contact->getKey(),
        'stage' => LeadStage::NEGOTIATION,
    ]);

    $deal = app(ConvertLeadToDeal::class)->execute($this->user, $lead);

    expect($deal->name)->toBe('Acme robotics line')
        ->and($deal->company_id)->toBe($company->getKey())
        ->and($deal->contact_id)->toBe($contact->getKey())
        ->and($deal->team_id)->toBe($this->team->getKey())
        ->and($deal->lead_id)->toBe($lead->getKey())
        ->and($deal->stage)->toBe(DealStage::OPPORTUNITY)
        ->and($deal->sub_stage)->toBe(DealSubStage::ASSIGNED_SALESPERSON);

    // The lead survives and records the handover.
    $lead->refresh();

    expect($lead->exists)->toBeTrue()
        ->and($lead->stage)->toBe(LeadStage::WON)
        ->and($lead->sub_stage)->toBe(LeadSubStage::CONVERTED_TO_DEAL)
        ->and($lead->deal->getKey())->toBe($deal->getKey());
});

it('refuses to convert the same lead twice', function (): void {
    $lead = Lead::factory()->recycle([$this->user, $this->team])->create();

    app(ConvertLeadToDeal::class)->execute($this->user, $lead);

    expect(fn (): Deal => app(ConvertLeadToDeal::class)->execute($this->user, $lead->refresh()))
        ->toThrow(ConflictHttpException::class);

    expect(Deal::query()->where('lead_id', $lead->getKey())->count())->toBe(1);
});

it('converts a deal into an order at the top of the fulfilment pipeline', function (): void {
    $company = Company::factory()->recycle([$this->user, $this->team])->create();

    $deal = Deal::factory()->recycle([$this->user, $this->team])->create([
        'name' => 'Acme robotics line',
        'company_id' => $company->getKey(),
        'stage' => DealStage::PAYMENT,
    ]);

    $order = app(ConvertDealToOrder::class)->execute($this->user, $deal);

    expect($order->name)->toBe('Acme robotics line')
        ->and($order->company_id)->toBe($company->getKey())
        ->and($order->deal_id)->toBe($deal->getKey())
        ->and($order->stage)->toBe(OrderStage::ORDER_RECEIVED);

    $deal->refresh();

    expect($deal->stage)->toBe(DealStage::WON)
        ->and($deal->sub_stage)->toBe(DealSubStage::MOVE_TO_ORDERS)
        ->and($order->deal->getKey())->toBe($deal->getKey());
});

it('refuses to convert the same deal twice', function (): void {
    $deal = Deal::factory()->recycle([$this->user, $this->team])->create();

    app(ConvertDealToOrder::class)->execute($this->user, $deal);

    expect(fn (): Order => app(ConvertDealToOrder::class)->execute($this->user, $deal->refresh()))
        ->toThrow(ConflictHttpException::class);

    expect(Order::query()->where('deal_id', $deal->getKey())->count())->toBe(1);
});

it('denies converting a lead belonging to another team', function (): void {
    $outsider = User::factory()->withTeam()->create();
    $foreignLead = Lead::factory()->for($outsider->currentTeam)->create();

    expect(fn (): Deal => app(ConvertLeadToDeal::class)->execute($this->user, $foreignLead))
        ->toThrow(HttpException::class);

    expect(Deal::query()->where('lead_id', $foreignLead->getKey())->exists())->toBeFalse();
});

it('denies converting a deal belonging to another team', function (): void {
    $outsider = User::factory()->withTeam()->create();
    $foreignDeal = Deal::factory()->for($outsider->currentTeam)->create();

    expect(fn (): Order => app(ConvertDealToOrder::class)->execute($this->user, $foreignDeal))
        ->toThrow(HttpException::class);

    expect(Order::query()->where('deal_id', $foreignDeal->getKey())->exists())->toBeFalse();
});

it('rolls the whole conversion back if the downstream write fails', function (): void {
    $lead = Lead::factory()->recycle([$this->user, $this->team])->create([
        'stage' => LeadStage::NEGOTIATION,
    ]);

    // A name longer than the column allows aborts the insert inside the
    // transaction; the lead's stage must not have moved.
    $lead->name = str_repeat('a', 300);

    try {
        app(ConvertLeadToDeal::class)->execute($this->user, $lead);
    } catch (Throwable) {
        // expected
    }

    expect($lead->fresh()->stage)->toBe(LeadStage::NEGOTIATION)
        ->and(Deal::query()->where('lead_id', $lead->getKey())->exists())->toBeFalse();
});
