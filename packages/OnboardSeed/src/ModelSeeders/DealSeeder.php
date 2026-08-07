<?php

declare(strict_types=1);

namespace Relaticle\OnboardSeed\ModelSeeders;

use App\Enums\CustomFields\DealField as DealCustomField;
use App\Enums\Pipeline\DealStage;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Relaticle\OnboardSeed\Support\BaseModelSeeder;
use Relaticle\OnboardSeed\Support\FixtureRegistry;

final class DealSeeder extends BaseModelSeeder
{
    protected string $modelClass = Deal::class;

    protected string $entityType = 'deals';

    protected array $fieldCodes = [
        DealCustomField::AMOUNT->value,
        DealCustomField::CLOSE_DATE->value,
    ];

    protected function createEntitiesFromFixtures(Team $team, Authenticatable $user): void
    {
        $fixtures = $this->loadEntityFixtures();

        foreach ($fixtures as $key => $data) {
            $companyKey = $data['company'] ?? null;

            if (! $companyKey) {
                Log::warning("Missing company reference for deal: {$key}");

                continue;
            }

            $company = FixtureRegistry::get('companies', $companyKey);

            if (! $company instanceof Company) {
                Log::warning("Company not found for deal: {$key}, company key: {$companyKey}");

                continue;
            }

            $this->createDealFromFixture($team, $user, $company, $key, $data);
        }
    }

    /**
     * Create an deal from fixture data
     *
     * @param  array<string, mixed>  $data
     */
    private function createDealFromFixture(
        Team $team,
        Authenticatable $user,
        Company $company,
        string $key,
        array $data
    ): Deal {
        $stage = is_string($data['stage'] ?? null)
            ? DealStage::tryFrom($data['stage'])
            : null;

        $attributes = [
            'name' => $data['name'],
            'company_id' => $company->id,
            'stage' => $stage ?? DealStage::OPPORTUNITY,
        ];

        $customFields = $data['custom_fields'] ?? [];

        // Define field mappings for custom processing
        $fieldMappings = [
            DealCustomField::CLOSE_DATE->value => fn (mixed $value): mixed => is_string($value) ? $this->evaluateTemplateExpression($value) : $value,
        ];

        // Process custom fields using utility method
        $processedFields = $this->processCustomFieldValues($customFields, $fieldMappings);

        /** @var Deal */
        return $this->registerEntityFromFixture($key, $attributes, $processedFields, $team, $user);
    }
}
