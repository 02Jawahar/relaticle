<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Note;

use App\Http\Resources\V1\NoteResource;
use App\Mcp\Tools\BaseAttachTool;
use App\Models\Note;
use App\Models\User;
use App\Rules\ArrayExistsForTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Attach a note to companies, people, or deals. Adds links without removing existing ones.')]
final class AttachNoteToEntitiesTool extends BaseAttachTool
{
    protected function modelClass(): string
    {
        return Note::class;
    }

    protected function entityLabel(): string
    {
        return 'Note';
    }

    protected function resourceClass(): string
    {
        return NoteResource::class;
    }

    /** @return array<int, string> */
    protected function relationshipsToLoad(): array
    {
        return ['companies', 'people', 'deals'];
    }

    public function relationshipSchema(JsonSchema $schema): array
    {
        return [
            'company_ids' => $schema->array()->description('Company IDs to attach this note to.'),
            'people_ids' => $schema->array()->description('People IDs to attach this note to.'),
            'deal_ids' => $schema->array()->description('Deal IDs to attach this note to.'),
        ];
    }

    public function relationshipRules(User $user): array
    {
        $teamId = $user->currentTeam->getKey();

        return [
            'company_ids' => ['sometimes', 'array'],
            'company_ids.*' => ['string', new ArrayExistsForTeam('companies', 'company_ids', $teamId)],
            'people_ids' => ['sometimes', 'array'],
            'people_ids.*' => ['string', new ArrayExistsForTeam('people', 'people_ids', $teamId)],
            'deal_ids' => ['sometimes', 'array'],
            'deal_ids.*' => ['string', new ArrayExistsForTeam('deals', 'deal_ids', $teamId)],
        ];
    }

    public function syncRelationships(Model $model, array $data): void
    {
        /** @var Note $model */
        if (isset($data['company_ids'])) {
            $model->companies()->syncWithoutDetaching($data['company_ids']);
        }

        if (isset($data['people_ids'])) {
            $model->people()->syncWithoutDetaching($data['people_ids']);
        }

        if (isset($data['deal_ids'])) {
            $model->deals()->syncWithoutDetaching($data['deal_ids']);
        }
    }
}
