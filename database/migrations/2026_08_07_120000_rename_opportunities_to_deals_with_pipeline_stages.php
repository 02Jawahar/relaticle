<?php

declare(strict_types=1);

use App\Enums\Pipeline\DealStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opportunities become Deals, and the pipeline stage moves off the
     * custom-fields tables onto first-class columns so a stage can carry a
     * validated sub-stage.
     */
    public function up(): void
    {
        if (Schema::hasTable('opportunities') && ! Schema::hasTable('deals')) {
            Schema::rename('opportunities', 'deals');
        }

        Schema::table('deals', function (Blueprint $table): void {
            if (! Schema::hasColumn('deals', 'stage')) {
                $table->string('stage')->default(DealStage::OPPORTUNITY->value)->index();
            }

            if (! Schema::hasColumn('deals', 'sub_stage')) {
                $table->string('sub_stage')->nullable();
            }
        });

        $this->renameMorphAlias();
        $this->migrateStageOntoColumn();
        $this->dropStageCustomField();
    }

    /**
     * Polymorphic relations store the morph-map alias, not the class name, so
     * every table holding the 'opportunity' alias has to be rewritten to 'deal'
     * or those rows stop resolving.
     */
    private function renameMorphAlias(): void
    {
        $aliasColumns = [
            'custom_fields' => 'entity_type',
            'custom_field_values' => 'entity_type',
            'noteables' => 'noteable_type',
            'taskables' => 'taskable_type',
            'activity_log' => 'subject_type',
        ];

        foreach ($aliasColumns as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, 'opportunity')->update([$column => 'deal']);
            }
        }
    }

    /**
     * Copy each deal's existing stage option into the new column. The former
     * option set was a generic Salesforce-style funnel, so it is folded onto the
     * closest equivalent in the new pipeline; anything unrecognised starts at
     * the top of the funnel rather than being silently dropped.
     */
    private function migrateStageOntoColumn(): void
    {
        $stageField = DB::table('custom_fields')
            ->where('entity_type', 'deal')
            ->where('code', 'stage')
            ->first();

        if ($stageField === null) {
            return;
        }

        $map = [
            'Prospecting' => DealStage::OPPORTUNITY,
            'Qualification' => DealStage::OPPORTUNITY,
            'Needs Analysis' => DealStage::SOLUTION_FINALIZED,
            'Value Proposition' => DealStage::SOLUTION_FINALIZED,
            'Id. Decision Makers' => DealStage::COMMERCIAL_DISCUSSION,
            'Perception Analysis' => DealStage::COMMERCIAL_DISCUSSION,
            'Proposal/Price Quote' => DealStage::COMMERCIAL_DISCUSSION,
            'Negotiation/Review' => DealStage::COMMERCIAL_DISCUSSION,
            'Closed Won' => DealStage::WON,
            'Closed Lost' => DealStage::LOST,
        ];

        $optionNames = DB::table('custom_field_options')
            ->where('custom_field_id', $stageField->id)
            ->pluck('name', 'id');

        $values = DB::table('custom_field_values')
            ->where('custom_field_id', $stageField->id)
            ->get(['entity_id', 'string_value']);

        foreach ($values as $value) {
            $optionName = $optionNames[$value->string_value] ?? null;
            $stage = $map[$optionName] ?? DealStage::OPPORTUNITY;

            DB::table('deals')
                ->where('id', $value->entity_id)
                ->update(['stage' => $stage->value]);
        }
    }

    /**
     * With the column in place the custom field would be a second source of
     * truth for the same fact, so it and its options/values are removed.
     */
    private function dropStageCustomField(): void
    {
        $stageFieldIds = DB::table('custom_fields')
            ->where('entity_type', 'deal')
            ->where('code', 'stage')
            ->pluck('id');

        if ($stageFieldIds->isEmpty()) {
            return;
        }

        DB::table('custom_field_values')->whereIn('custom_field_id', $stageFieldIds)->delete();
        DB::table('custom_field_options')->whereIn('custom_field_id', $stageFieldIds)->delete();
        DB::table('custom_fields')->whereIn('id', $stageFieldIds)->delete();
    }
};
