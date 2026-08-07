<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Task;

use App\Http\Resources\V1\TaskResource;
use App\Mcp\Tools\BaseDetachTool;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Rules\ArrayExistsForTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Detach a task from companies, people, deals, or unassign users. Removes specified links.')]
final class DetachTaskFromEntitiesTool extends BaseDetachTool
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
            'company_ids' => $schema->array()->description('Company IDs to detach from this task.'),
            'people_ids' => $schema->array()->description('People IDs to detach from this task.'),
            'deal_ids' => $schema->array()->description('Deal IDs to detach from this task.'),
            'assignee_ids' => $schema->array()->description('User IDs to unassign from this task.'),
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

    public function detachRelationships(Model $model, array $data): void
    {
        /** @var Task $model */
        if (isset($data['company_ids'])) {
            $model->companies()->detach($data['company_ids']);
        }

        if (isset($data['people_ids'])) {
            $model->people()->detach($data['people_ids']);
        }

        if (isset($data['deal_ids'])) {
            $model->deals()->detach($data['deal_ids']);
        }

        if (isset($data['assignee_ids'])) {
            $model->assignees()->detach($data['assignee_ids']);
        }
    }
}
