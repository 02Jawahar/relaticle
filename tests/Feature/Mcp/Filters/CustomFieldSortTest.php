<?php

declare(strict_types=1);

use App\Mcp\Filters\CustomFieldSort;
use App\Models\CustomField;
use App\Models\Deal;
use App\Models\Scopes\TeamScope;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
    $this->actingAs($this->user);
    Deal::addGlobalScope(new TeamScope);
});

afterEach(function (): void {
    Deal::clearBootedModels();
});

it('sorts deals by custom field value ascending', function (): void {
    $opp1 = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'A']);
    $opp2 = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'B']);

    $amountField = CustomField::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $this->team->getKey())
        ->where('entity_type', 'deal')
        ->where('code', 'amount')
        ->first();

    expect($amountField)->not->toBeNull();

    $opp1->saveCustomFieldValue($amountField, 50000);
    $opp2->saveCustomFieldValue($amountField, 100000);

    $request = new Request(['sort' => 'amount']);

    $results = QueryBuilder::for(
        Deal::query()->where('team_id', $this->team->getKey())->withCustomFieldValues(),
        $request,
    )
        ->allowedSorts(
            AllowedSort::custom('amount', new CustomFieldSort('deal')),
        )
        ->get();

    $names = $results->pluck('name')->values();
    $indexA = $names->search('A');
    $indexB = $names->search('B');

    expect($indexA)->toBeLessThan($indexB);
});

it('sorts deals by custom field value descending', function (): void {
    $opp1 = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'A']);
    $opp2 = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'B']);

    $amountField = CustomField::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $this->team->getKey())
        ->where('entity_type', 'deal')
        ->where('code', 'amount')
        ->first();

    expect($amountField)->not->toBeNull();

    $opp1->saveCustomFieldValue($amountField, 50000);
    $opp2->saveCustomFieldValue($amountField, 100000);

    $request = new Request(['sort' => '-amount']);

    $results = QueryBuilder::for(
        Deal::query()->where('team_id', $this->team->getKey())->withCustomFieldValues(),
        $request,
    )
        ->allowedSorts(
            AllowedSort::custom('amount', new CustomFieldSort('deal')),
        )
        ->get();

    $names = $results->pluck('name')->values();
    $indexA = $names->search('A');
    $indexB = $names->search('B');

    expect($indexB)->toBeLessThan($indexA);
});
