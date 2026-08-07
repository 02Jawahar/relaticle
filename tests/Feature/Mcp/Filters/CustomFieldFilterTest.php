<?php

declare(strict_types=1);

use App\Mcp\Filters\CustomFieldFilter;
use App\Models\CustomField;
use App\Models\Deal;
use App\Models\Scopes\TeamScope;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
    $this->actingAs($this->user);
    Deal::addGlobalScope(new TeamScope);
});

afterEach(function (): void {
    Deal::clearBootedModels();
});

it('filters by custom field equality', function (): void {
    $deal1 = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'Deal A']);
    $deal2 = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'Deal B']);

    // Owns its field rather than leaning on whichever ones happen to be seeded:
    // this test covers the filter, not the tenant's default field set.
    $territoryField = CustomField::factory()->create([
        'tenant_id' => $this->team->getKey(),
        'entity_type' => 'deal',
        'code' => 'territory',
        'name' => 'Territory',
        'type' => 'text',
    ]);

    $deal1->saveCustomFieldValue($territoryField, 'EMEA');
    $deal2->saveCustomFieldValue($territoryField, 'APAC');

    $request = new Request([
        'filter' => [
            'custom_fields' => [
                'territory' => ['eq' => 'EMEA'],
            ],
        ],
    ]);

    $results = QueryBuilder::for(Deal::query()->withCustomFieldValues(), $request)
        ->allowedFilters(
            AllowedFilter::custom('custom_fields', new CustomFieldFilter('deal')),
        )
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Deal A');
});

it('filters by currency field with gte operator', function (): void {
    $deal1 = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'Big Deal']);
    $deal2 = Deal::factory()->recycle([$this->user, $this->team])->create(['name' => 'Small Deal']);

    $amountField = CustomField::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $this->team->getKey())
        ->where('entity_type', 'deal')
        ->where('code', 'amount')
        ->first();

    expect($amountField)->not->toBeNull('Amount custom field must exist for this test');

    $deal1->saveCustomFieldValue($amountField, 100000);
    $deal2->saveCustomFieldValue($amountField, 5000);

    $request = new Request([
        'filter' => [
            'custom_fields' => [
                'amount' => ['gte' => 50000],
            ],
        ],
    ]);

    $results = QueryBuilder::for(Deal::query()->withCustomFieldValues(), $request)
        ->allowedFilters(
            AllowedFilter::custom('custom_fields', new CustomFieldFilter('deal')),
        )
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Big Deal');
});

it('silently ignores unknown field codes', function (): void {
    $countBefore = Deal::query()->count();

    Deal::factory()->recycle([$this->user, $this->team])->create();

    $request = new Request([
        'filter' => [
            'custom_fields' => [
                'nonexistent_field' => ['eq' => 'test'],
            ],
        ],
    ]);

    $results = QueryBuilder::for(Deal::query()->withCustomFieldValues(), $request)
        ->allowedFilters(
            AllowedFilter::custom('custom_fields', new CustomFieldFilter('deal')),
        )
        ->get();

    expect($results)->toHaveCount($countBefore + 1);
});

it('rejects more than 10 filter conditions', function (): void {
    $filters = [];

    for ($i = 0; $i < 11; $i++) {
        $filters["field_{$i}"] = ['eq' => 'test'];
    }

    $request = new Request([
        'filter' => ['custom_fields' => $filters],
    ]);

    QueryBuilder::for(Deal::query()->withCustomFieldValues(), $request)
        ->allowedFilters(
            AllowedFilter::custom('custom_fields', new CustomFieldFilter('deal')),
        )
        ->get();
})->throws(HttpException::class);

it('handles empty filter object as no-op', function (): void {
    $countBefore = Deal::query()->count();

    Deal::factory()->recycle([$this->user, $this->team])->count(3)->create();

    $request = new Request([
        'filter' => [
            'custom_fields' => [],
        ],
    ]);

    $results = QueryBuilder::for(Deal::query()->withCustomFieldValues(), $request)
        ->allowedFilters(
            AllowedFilter::custom('custom_fields', new CustomFieldFilter('deal')),
        )
        ->get();

    expect($results)->toHaveCount($countBefore + 3);
});
