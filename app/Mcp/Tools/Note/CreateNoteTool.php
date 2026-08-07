<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Note;

use App\Actions\Note\CreateNote;
use App\Http\Resources\V1\NoteResource;
use App\Mcp\Tools\BaseCreateTool;
use App\Models\User;
use App\Rules\ArrayExistsForTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a new note in the CRM. Use the crm-schema resource to discover available custom fields.')]
final class CreateNoteTool extends BaseCreateTool
{
    protected function actionClass(): string
    {
        return CreateNote::class;
    }

    protected function resourceClass(): string
    {
        return NoteResource::class;
    }

    protected function entityType(): string
    {
        return 'note';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The note title.')->required(),
            'company_ids' => $schema->array()->description('Company IDs to link this note to.'),
            'people_ids' => $schema->array()->description('People IDs to link this note to.'),
            'deal_ids' => $schema->array()->description('Deal IDs to link this note to.'),
        ];
    }

    protected function entityRules(User $user): array
    {
        $teamId = $user->currentTeam->getKey();

        return [
            'title' => ['required', 'string', 'max:255'],
            'company_ids' => ['sometimes', 'array'],
            'company_ids.*' => ['string', new ArrayExistsForTeam('companies', 'company_ids', $teamId)],
            'people_ids' => ['sometimes', 'array'],
            'people_ids.*' => ['string', new ArrayExistsForTeam('people', 'people_ids', $teamId)],
            'deal_ids' => ['sometimes', 'array'],
            'deal_ids.*' => ['string', new ArrayExistsForTeam('deals', 'deal_ids', $teamId)],
        ];
    }
}
