<?php

declare(strict_types=1);

use App\Mcp\Servers\RelaticleServer;
use App\Mcp\Tools\Deal\CreateDealTool;
use App\Mcp\Tools\Deal\DeleteDealTool;
use App\Mcp\Tools\Deal\GetDealTool;
use App\Mcp\Tools\Deal\ListDealsTool;
use App\Mcp\Tools\Deal\UpdateDealTool;
use App\Models\Company;
use App\Models\Deal;
use App\Models\People;
use App\Models\Scopes\TeamScope;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

afterEach(function () {
    Deal::clearBootedModels();
});

it('can get an deal by ID', function (): void {
    $deal = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'Big Deal']);

    RelaticleServer::actingAs($this->user)
        ->tool(GetDealTool::class, ['id' => $deal->id])
        ->assertOk()
        ->assertSee('Big Deal');
});

it('can update an deal via MCP tool', function (): void {
    $deal = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'Old Deal']);

    RelaticleServer::actingAs($this->user)
        ->tool(UpdateDealTool::class, [
            'id' => $deal->id,
            'name' => 'New Deal',
        ])
        ->assertOk()
        ->assertSee('New Deal');

    expect($deal->refresh()->name)->toBe('New Deal');
});

it('can delete an deal via MCP tool', function (): void {
    $deal = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'Closing Deal']);

    RelaticleServer::actingAs($this->user)
        ->tool(DeleteDealTool::class, [
            'id' => $deal->id,
        ])
        ->assertOk()
        ->assertSee('has been deleted');

    expect($deal->refresh()->trashed())->toBeTrue();
});

it('can filter deals by contact_id', function (): void {
    $person = People::factory()->recycle([$this->user, $this->team])->create();
    $matchingOpp = Deal::factory()->recycle([$this->user, $this->team])->create([
        'contact_id' => $person->id,
    ]);
    $otherOpp = Deal::factory()->recycle([$this->user, $this->team])->create();

    RelaticleServer::actingAs($this->user)
        ->tool(ListDealsTool::class, [
            'contact_id' => $person->id,
        ])
        ->assertOk()
        ->assertSee($matchingOpp->name)
        ->assertDontSee($otherOpp->name);
});

describe('team scoping', function () {
    beforeEach(function () {
        Deal::addGlobalScope(new TeamScope);
    });

    it('scopes deals to current team', function (): void {
        $otherDeal = Deal::withoutEvents(fn () => Deal::factory()->create([
            'team_id' => Team::factory()->create()->id,
            'name' => 'Other Team Deal',
        ]));
        $ownDeal = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'Own Team Deal']);

        RelaticleServer::actingAs($this->user)
            ->tool(ListDealsTool::class)
            ->assertOk()
            ->assertSee('Own Team Deal')
            ->assertDontSee('Other Team Deal');
    });

    it('cannot update an deal from another team', function (): void {
        $otherDeal = Deal::withoutEvents(fn () => Deal::factory()->create([
            'team_id' => Team::factory()->create()->id,
        ]));

        RelaticleServer::actingAs($this->user)
            ->tool(UpdateDealTool::class, [
                'id' => $otherDeal->id,
                'name' => 'Hacked',
            ])
            ->assertHasErrors(['not found']);
    });

    it('cannot delete an deal from another team', function (): void {
        $otherDeal = Deal::withoutEvents(fn () => Deal::factory()->create([
            'team_id' => Team::factory()->create()->id,
        ]));

        RelaticleServer::actingAs($this->user)
            ->tool(DeleteDealTool::class, [
                'id' => $otherDeal->id,
            ])
            ->assertHasErrors(['not found']);
    });

    it('cannot get an deal from another team', function (): void {
        $otherDeal = Deal::withoutEvents(fn () => Deal::factory()->create([
            'team_id' => Team::factory()->create()->id,
        ]));

        RelaticleServer::actingAs($this->user)
            ->tool(GetDealTool::class, [
                'id' => $otherDeal->id,
            ])
            ->assertHasErrors(['not found']);
    });

    it('rejects company_id from another team when creating deal', function (): void {
        $otherTeam = Team::factory()->create();
        $otherCompany = Company::withoutEvents(fn () => Company::factory()->create([
            'team_id' => $otherTeam->id,
        ]));

        RelaticleServer::actingAs($this->user)
            ->tool(CreateDealTool::class, [
                'name' => 'Test Deal',
                'company_id' => $otherCompany->id,
            ])
            ->assertHasErrors();
    });
});
