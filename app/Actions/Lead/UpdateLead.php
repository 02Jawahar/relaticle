<?php

declare(strict_types=1);

namespace App\Actions\Lead;

use App\Models\Company;
use App\Models\Lead;
use App\Models\People;
use App\Models\User;
use App\Support\CustomFieldMerger;
use App\Support\TenantFkValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateLead
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Lead $lead, array $data): Lead
    {
        abort_unless($user->can('update', $lead), 403);

        TenantFkValidator::assertOwned($user, $data, [
            'company_id' => Company::class,
            'contact_id' => People::class,
        ]);

        $attributes = Arr::only($data, ['name', 'company_id', 'contact_id', 'stage', 'sub_stage', 'custom_fields']);

        $attributes = CustomFieldMerger::merge($lead, $attributes);

        return DB::transaction(function () use ($lead, $attributes): Lead {
            $lead->update($attributes);

            return $lead->refresh()->load('customFieldValues.customField.options');
        });
    }
}
