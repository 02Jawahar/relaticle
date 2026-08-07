<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\Deal;
use App\Models\People;
use App\Models\User;
use App\Support\TenantFkValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class CreateDeal
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, CreationSource $source = CreationSource::WEB): Deal
    {
        abort_unless($user->can('create', Deal::class), 403);

        TenantFkValidator::assertOwned($user, $data, [
            'company_id' => Company::class,
            'contact_id' => People::class,
        ]);

        $attributes = Arr::only($data, ['name', 'company_id', 'contact_id', 'custom_fields']);
        $attributes['creation_source'] = $source;

        $deal = DB::transaction(fn (): Deal => Deal::query()->create($attributes));

        return $deal->load('customFieldValues.customField.options');
    }
}
