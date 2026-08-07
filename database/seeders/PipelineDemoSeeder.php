<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CustomFields\DealField;
use App\Enums\CustomFields\LeadField;
use App\Enums\CustomFields\OrderField;
use App\Enums\Pipeline\DealStage;
use App\Enums\Pipeline\LeadStage;
use App\Enums\Pipeline\OrderStage;
use App\Models\Company;
use App\Models\CustomField;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Services\TenantContextService;

/**
 * Fills the Lead, Deal and Order pipelines with demo records so every stage has
 * something in it.
 *
 * Local-only developer data, per the project guideline that environment-specific
 * fixtures live in seeders rather than behind environment checks in app code.
 * Re-running is safe: records are matched on name per team.
 */
final class PipelineDemoSeeder extends Seeder
{
    /**
     * Each entry seeds one card. The stage index maps onto the pipeline's
     * declared case order, so every stage gets covered without naming them twice.
     *
     * @var list<array{name: string, value: int}>
     */
    private const array LEADS = [
        ['name' => 'Metro Hospitals — ward automation', 'value' => 18000],
        ['name' => 'Redline Logistics — fleet trackers', 'value' => 42000],
        ['name' => 'Kavery Textiles — floor sensors', 'value' => 26500],
        ['name' => 'Anand Motors — dealership kiosks', 'value' => 15750],
        ['name' => 'Sunrise Schools — campus wifi', 'value' => 33000],
        ['name' => 'Harbour Foods — cold chain monitors', 'value' => 57000],
        ['name' => 'Vertex Pharma — cleanroom controls', 'value' => 96000],
        ['name' => 'BlueOak Realty — access control', 'value' => 21000],
        ['name' => 'Nova Energy — substation telemetry', 'value' => 128000],
        ['name' => 'Peak Retail — smart shelving', 'value' => 47500],
        ['name' => 'Orion Freight — yard cameras', 'value' => 39000],
    ];

    /** @var list<array{name: string, value: int}> */
    private const array DEALS = [
        ['name' => 'Coastal Grid — meter rollout', 'value' => 74000],
        ['name' => 'Tamil Spinners — line retrofit', 'value' => 52500],
        ['name' => 'Greenfield Agro — irrigation control', 'value' => 61000],
        ['name' => 'Meridian Hotels — room controllers', 'value' => 88000],
        ['name' => 'Ironclad Steel — furnace sensors', 'value' => 143000],
        ['name' => 'CityCare Clinics — asset tags', 'value' => 29500],
        ['name' => 'Skyline Towers — lift telemetry', 'value' => 67000],
        ['name' => 'Pioneer Dairy — tank monitoring', 'value' => 45000],
        ['name' => 'Quantum Labs — bench instrumentation', 'value' => 112000],
        ['name' => 'Sagar Ports — crane diagnostics', 'value' => 205000],
        ['name' => 'Vista Malls — footfall counters', 'value' => 36000],
    ];

    /** @var list<array{name: string, value: int}> */
    private const array ORDERS = [
        ['name' => 'ORD-2041 — Coastal Grid meters', 'value' => 74000],
        ['name' => 'ORD-2042 — Tamil Spinners retrofit', 'value' => 52500],
        ['name' => 'ORD-2043 — Greenfield irrigation', 'value' => 61000],
        ['name' => 'ORD-2044 — Meridian controllers', 'value' => 88000],
        ['name' => 'ORD-2045 — Ironclad sensors', 'value' => 143000],
        ['name' => 'ORD-2046 — CityCare asset tags', 'value' => 29500],
        ['name' => 'ORD-2047 — Skyline lift telemetry', 'value' => 67000],
        ['name' => 'ORD-2048 — Pioneer tank monitors', 'value' => 45000],
        ['name' => 'ORD-2049 — Quantum bench kit', 'value' => 112000],
        ['name' => 'ORD-2050 — Sagar crane diagnostics', 'value' => 205000],
        ['name' => 'ORD-2051 — Vista footfall counters', 'value' => 36000],
    ];

    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command?->info('Skipping pipeline demo data: environment is not local.');

            return;
        }

        Team::query()->withoutGlobalScopes()->cursor()->each($this->seedTeam(...));
    }

    private function seedTeam(Team $team): void
    {
        /** @var User|null $creator */
        $creator = $team->owner;

        if ($creator === null) {
            return;
        }

        $companies = Company::query()->withoutGlobalScopes()
            ->where('team_id', $team->getKey())->get();
        $contacts = People::query()->withoutGlobalScopes()
            ->where('team_id', $team->getKey())->get();

        // Custom-field writes outside a panel request need the tenant set
        // explicitly, or saveCustomFields would walk every tenant.
        $previous = TenantContextService::getCurrentTenantId();
        TenantContextService::setTenantId($team->getKey());

        try {
            $this->seedPipeline($team, $creator, $companies, $contacts, Lead::class, LeadStage::cases(), self::LEADS, [
                LeadField::ESTIMATED_VALUE->value => 'value',
                LeadField::EXPECTED_CLOSE_DATE->value => 'date',
            ]);

            $this->seedPipeline($team, $creator, $companies, $contacts, Deal::class, DealStage::cases(), self::DEALS, [
                DealField::AMOUNT->value => 'value',
                DealField::CLOSE_DATE->value => 'date',
            ]);

            $this->seedPipeline($team, $creator, $companies, $contacts, Order::class, OrderStage::cases(), self::ORDERS, [
                OrderField::ORDER_VALUE->value => 'value',
                OrderField::EXPECTED_DELIVERY_DATE->value => 'date',
            ]);
        } finally {
            TenantContextService::setTenantId($previous);
        }

        $this->command?->info("Pipeline demo data ready for team [{$team->name}].");
    }

    /**
     * @param  Collection<int, Company>  $companies
     * @param  Collection<int, People>  $contacts
     * @param  class-string<Model>  $model
     * @param  list<\BackedEnum>  $stages
     * @param  list<array{name: string, value: int}>  $rows
     * @param  array<string, 'value'|'date'>  $fieldMap
     */
    private function seedPipeline(
        Team $team,
        User $creator,
        Collection $companies,
        Collection $contacts,
        string $model,
        array $stages,
        array $rows,
        array $fieldMap,
    ): void {
        foreach ($rows as $index => $row) {
            $stage = $stages[$index % count($stages)];

            /** @var Model|null $existing */
            $existing = $model::query()->withoutGlobalScopes()
                ->where('team_id', $team->getKey())
                ->where('name', $row['name'])
                ->first();

            if ($existing !== null) {
                continue;
            }

            $company = $companies->isNotEmpty() ? $companies[$index % $companies->count()] : null;
            $contact = $contacts->isNotEmpty() ? $contacts[$index % $contacts->count()] : null;

            /** @var Model $record */
            $record = $model::query()->create([
                'team_id' => $team->getKey(),
                'creator_id' => $creator->getKey(),
                'company_id' => $company?->getKey(),
                'contact_id' => $contact?->getKey(),
                'name' => $row['name'],
                'stage' => $stage,
                'sub_stage' => $stage->subStages()[0] ?? null,
                'order_column' => ($index + 1) * 1000,
            ]);

            $this->applyCustomFields($record, $team, $fieldMap, $row['value'], $index);
        }
    }

    /**
     * @param  array<string, 'value'|'date'>  $fieldMap
     */
    private function applyCustomFields(Model $record, Team $team, array $fieldMap, int $value, int $index): void
    {
        foreach ($fieldMap as $code => $kind) {
            $field = CustomField::query()->withoutGlobalScopes()
                ->where('tenant_id', $team->getKey())
                ->where('entity_type', $record->getMorphClass())
                ->where('code', $code)
                ->first();

            if (! $field instanceof CustomField) {
                continue;
            }

            $record->saveCustomFieldValue(
                $field,
                $kind === 'value'
                    ? $value
                    : Carbon::now()->addDays(7 + ($index * 5))->toDateString(),
            );
        }
    }
}
