<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Task;

use App\Actions\Task\CreateTask;
use App\Models\Company;
use App\Models\Deal;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Relaticle\Chat\Support\TeamMembersContext;
use Relaticle\Chat\Tools\BaseWriteCreateTool;
use Relaticle\Chat\Tools\Concerns\NormalizesToolInput;

final class CreateTaskTool extends BaseWriteCreateTool
{
    use NormalizesToolInput;

    public function description(): string
    {
        return 'Propose creating a new task. Optionally link to people, companies, deals, and assign users.';
    }

    protected function actionClass(): string
    {
        return CreateTask::class;
    }

    protected function entityType(): string
    {
        return 'task';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The task title.')->required(),
            'assignee_ids' => $schema->array()->description('User ULIDs to assign.'),
            'people_ids' => $schema->array()->description('People (contact) ULIDs to link.'),
            'company_ids' => $schema->array()->description('Company ULIDs to link.'),
            'deal_ids' => $schema->array()->description('Deal ULIDs to link.'),
        ];
    }

    protected function validateRecord(array $record, User $user): ?string
    {
        return TeamMembersContext::memberFieldError($user, 'assignee_ids', $record['assignee_ids'] ?? null);
    }

    protected function extractRecordData(array $record): array
    {
        return array_filter([
            'title' => (string) ($record['title'] ?? ''),
            'assignee_ids' => $this->idListFromArray($record, 'assignee_ids'),
            'people_ids' => $this->idListFromArray($record, 'people_ids'),
            'company_ids' => $this->idListFromArray($record, 'company_ids'),
            'deal_ids' => $this->idListFromArray($record, 'deal_ids'),
        ], static fn (mixed $v): bool => ! in_array($v, [null, '', []], true));
    }

    protected function buildRecordDisplay(array $record): array
    {
        /** @var User $user */
        $user = auth()->user();
        $team = $user->currentTeam;

        $title = (string) ($record['title'] ?? '');
        $fields = [['label' => 'Title', 'value' => $title]];

        $peopleNames = $this->namesForIds($this->idListFromArray($record, 'people_ids'), People::class, 'name', $team);
        if ($peopleNames !== '') {
            $fields[] = ['label' => 'Linked people', 'value' => $peopleNames];
        }

        $companyNames = $this->namesForIds($this->idListFromArray($record, 'company_ids'), Company::class, 'name', $team);
        if ($companyNames !== '') {
            $fields[] = ['label' => 'Linked companies', 'value' => $companyNames];
        }

        $dealNames = $this->namesForIds($this->idListFromArray($record, 'deal_ids'), Deal::class, 'name', $team);
        if ($dealNames !== '') {
            $fields[] = ['label' => 'Linked deals', 'value' => $dealNames];
        }

        $assigneeNames = $this->namesForIds($this->idListFromArray($record, 'assignee_ids'), User::class, 'name', null);
        if ($assigneeNames !== '') {
            $fields[] = ['label' => 'Assignees', 'value' => $assigneeNames];
        }

        return [
            'title' => 'Create Task',
            'summary' => "Create task \"{$title}\"",
            'fields' => $fields,
        ];
    }

    /**
     * @param  list<string>|null  $ids
     * @param  class-string<Model>  $modelClass
     */
    private function namesForIds(?array $ids, string $modelClass, string $nameAttribute, ?Team $team): string
    {
        if ($ids === null || $ids === []) {
            return '';
        }

        $instance = new $modelClass;
        $query = $modelClass::query()->whereIn($instance->getKeyName(), $ids);
        if ($team instanceof Team) {
            $query->where('team_id', $team->getKey());
        }

        return $query->pluck($nameAttribute)->implode(', ');
    }
}
