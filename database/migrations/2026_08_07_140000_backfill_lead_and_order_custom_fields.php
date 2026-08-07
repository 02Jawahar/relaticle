<?php

declare(strict_types=1);

use App\Enums\CustomFields\LeadField;
use App\Enums\CustomFields\OrderField;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Contracts\CustomsFieldsMigrators;
use Relaticle\CustomFields\Data\CustomFieldData;
use Relaticle\CustomFields\Data\CustomFieldSectionData;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;

return new class extends Migration
{
    /**
     * CreateTeamCustomFields only fires on TeamCreated, so teams that already
     * exist would never receive the Lead and Order fields. Backfilled here,
     * skipping any a team already has so this stays safe to re-run.
     */
    public function up(): void
    {
        /** @var CustomsFieldsMigrators $migrator */
        $migrator = resolve(CustomsFieldsMigrators::class);

        $entities = [
            Lead::class => LeadField::cases(),
            Order::class => OrderField::cases(),
        ];

        Team::query()->withoutGlobalScopes()->cursor()->each(
            function (Team $team) use ($migrator, $entities): void {
                $migrator->setTenantId($team->getKey());

                foreach ($entities as $model => $fields) {
                    foreach ($fields as $field) {
                        if ($this->alreadyExists($team->getKey(), $model, $field->value)) {
                            continue;
                        }

                        $migrator->new(
                            model: $model,
                            fieldData: new CustomFieldData(
                                name: $field->getDisplayName(),
                                code: $field->value,
                                type: $field->getFieldType(),
                                section: new CustomFieldSectionData(
                                    name: 'General',
                                    code: 'general',
                                    type: CustomFieldSectionType::HEADLESS,
                                ),
                                systemDefined: $field->isSystemDefined(),
                                width: $field->getWidth(),
                                settings: new CustomFieldSettingsData(
                                    list_toggleable_hidden: $field->isListToggleableHidden(),
                                    enable_option_colors: $field->hasColorOptions(),
                                    allow_multiple: $field->allowsMultipleValues(),
                                    max_values: $field->getMaxValues(),
                                    unique_per_entity_type: $field->isUniquePerEntityType(),
                                ),
                            ),
                        )->create();
                    }
                }
            }
        );
    }

    private function alreadyExists(mixed $tenantId, string $model, string $code): bool
    {
        return DB::table('custom_fields')
            ->where('tenant_id', $tenantId)
            ->where('entity_type', (new $model)->getMorphClass())
            ->where('code', $code)
            ->exists();
    }
};
