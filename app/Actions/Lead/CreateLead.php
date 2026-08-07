<?php

declare(strict_types=1);

namespace App\Actions\Lead;

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\Lead;
use App\Models\People;
use App\Models\User;
use App\Support\TenantFkValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class CreateLead
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, CreationSource $source = CreationSource::WEB): Lead
    {
        abort_unless($user->can('create', Lead::class), 403);

        TenantFkValidator::assertOwned($user, $data, [
            'company_id' => Company::class,
            'contact_id' => People::class,
        ]);

        $attributes = Arr::only($data, ['name', 'company_id', 'contact_id', 'stage', 'sub_stage', 'order_column', 'custom_fields']);
        $attributes['creation_source'] = $source;

        $lead = DB::transaction(fn (): Lead => Lead::query()->create($attributes));

        return $lead->load('customFieldValues.customField.options');
    }
}
