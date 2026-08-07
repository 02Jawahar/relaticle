<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Task;

use App\Http\Resources\V1\TaskResource;
use App\Mcp\Tools\BaseAttachTool;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Rules\ArrayExistsForTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Attach a task to companies, people, deals, or assign to users. Adds links without removing existing ones.')]
final class AttachTaskToEntitiesTool extends BaseAttachTool
{
    protected function modelClass(): string
    {
        return Task::class;
    }

    protected function entityLabel(): string
    {
        return 'Task';
    }

    protected function resourceClass(): string
    {
        return TaskResource::class;
    }

    /** @return array<int, string> */
    protected function relationshipsToLoad(): array
    {
        return ['companies', 'people', 'deals', 'assignees'];
    }

    public function relationshipSchema(JsonSchema $schema): array
    {
        return [
            'company_ids' => $schema->array()->description('Company IDs to attach this task to.'),
            'people_ids' => $schema->array()->description('People IDs to attach this task to.'),
            'deal_ids' => $schema->array()->description('Deal IDs to attach this task to.'),
            'assignee_ids' => $schema->array()->description('User IDs to assign this task to. Use whoami tool to discover valid user IDs.'),
        ];
    }

    public function relationshipRules(User $user): array
    {
        /** @var Team $team */
        $team = $user->currentTeam;
        $teamId = $team->getKey();
        $teamMemberIds = $team->allUsers()->pluck('id')->all();

        return [
            'company_ids' => ['sometimes', 'array'],
            'company_ids.*' => ['string', new ArrayExistsForTeam('companies', 'company_ids', $teamId)],
            'people_ids' => ['sometimes', 'array'],
            'people_ids.*' => ['string', new ArrayExistsForTeam('people', 'people_ids', $teamId)],
            'deal_ids' => ['sometimes', 'array'],
            'deal_ids.*' => ['string', new ArrayExistsForTeam('deals', 'deal_ids', $teamId)],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['string', Rule::in($teamMemberIds)],
        ];
    }

    public function syncRelationships(Model $model, array $data): void
    {
        /** @var Task $model */
        if (isset($data['company_ids'])) {
            $model->companies()->syncWithoutDetaching($data['company_ids']);
        }

        if (isset($data['people_ids'])) {
            $model->people()->syncWithoutDetaching($data['people_ids']);
        }

        if (isset($data['deal_ids'])) {
            $model->deals()->syncWithoutDetaching($data['deal_ids']);
        }

        if (isset($data['assignee_ids'])) {
            $model->assignees()->syncWithoutDetaching($data['assignee_ids']);
        }
    }
}
