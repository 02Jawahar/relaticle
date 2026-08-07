<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deal;

use App\Actions\Deal\CreateDeal;
use App\Http\Resources\V1\DealResource;
use App\Mcp\Tools\BaseCreateTool;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a new deal (deal) in the CRM. Use the crm-schema resource to discover available custom fields.')]
final class CreateDealTool extends BaseCreateTool
{
    protected function actionClass(): string
    {
        return CreateDeal::class;
    }

    protected function resourceClass(): string
    {
        return DealResource::class;
    }

    protected function entityType(): string
    {
        return 'deal';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The deal name.')->required(),
            'company_id' => $schema->string()->description('The associated company ID.'),
            'contact_id' => $schema->string()->description('The associated contact (person) ID.'),
        ];
    }

    protected function entityRules(User $user): array
    {
        $teamId = $user->currentTeam->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['sometimes', 'nullable', 'string', Rule::exists('companies', 'id')->where('team_id', $teamId)],
            'contact_id' => ['sometimes', 'nullable', 'string', Rule::exists('people', 'id')->where('team_id', $teamId)],
        ];
    }
}
