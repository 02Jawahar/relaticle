<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Models\Company;
use App\Models\Deal;
use App\Models\People;
use App\Models\User;
use App\Support\CustomFieldMerger;
use App\Support\TenantFkValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateDeal
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Deal $deal, array $data): Deal
    {
        abort_unless($user->can('update', $deal), 403);

        TenantFkValidator::assertOwned($user, $data, [
            'company_id' => Company::class,
            'contact_id' => People::class,
        ]);

        $attributes = Arr::only($data, ['name', 'company_id', 'contact_id', 'custom_fields']);

        $attributes = CustomFieldMerger::merge($deal, $attributes);

        return DB::transaction(function () use ($deal, $attributes): Deal {
            $deal->update($attributes);

            return $deal->refresh()->load('customFieldValues.customField.options');
        });
    }
}
