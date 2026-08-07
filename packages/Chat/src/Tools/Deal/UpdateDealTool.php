<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Deal;

use App\Actions\Deal\UpdateDeal;
use App\Models\Company;
use App\Models\Deal;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Tools\Request;
use Relaticle\Chat\Tools\BaseWriteUpdateTool;

final class UpdateDealTool extends BaseWriteUpdateTool
{
    public function description(): string
    {
        return 'Propose updating an existing deal/deal, including its linked company and primary contact.';
    }

    protected function modelClass(): string
    {
        return Deal::class;
    }

    protected function actionClass(): string
    {
        return UpdateDeal::class;
    }

    protected function entityType(): string
    {
        return 'deal';
    }

    protected function entityLabel(): string
    {
        return 'Deal';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The new deal name.'),
            'company_id' => $schema->string()->description('The new linked company ULID.'),
            'contact_id' => $schema->string()->description('The new linked primary contact (people) ULID.'),
        ];
    }

    protected function extractActionData(Request $request): array
    {
        return array_filter([
            'name' => $request['name'] ?? null,
            'company_id' => $request['company_id'] ?? null,
            'contact_id' => $request['contact_id'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');
    }

    protected function buildDisplayData(Request $request, Model $model): array
    {
        /** @var User $user */
        $user = auth()->user();
        $team = $user->currentTeam;

        $fields = [];

        if (($request['name'] ?? null) !== null && $request['name'] !== '') {
            $fields[] = [
                'label' => 'Name',
                'old' => $model->getAttribute('name'),
                'new' => $request['name'],
            ];
        }

        $newCompanyId = $this->stringOrNull($request, 'company_id');
        if ($newCompanyId !== null) {
            $fields[] = [
                'label' => 'Company',
                'old' => $this->nameForId($model->getAttribute('company_id'), Company::class, 'name', $team),
                'new' => $this->nameForId($newCompanyId, Company::class, 'name', $team),
            ];
        }

        $newContactId = $this->stringOrNull($request, 'contact_id');
        if ($newContactId !== null) {
            $fields[] = [
                'label' => 'Contact',
                'old' => $this->nameForId($model->getAttribute('contact_id'), People::class, 'name', $team),
                'new' => $this->nameForId($newContactId, People::class, 'name', $team),
            ];
        }

        return [
            'title' => 'Update Deal',
            'summary' => "Update deal \"{$model->getAttribute('name')}\"",
            'fields' => $fields,
        ];
    }

    private function stringOrNull(Request $request, string $key): ?string
    {
        $value = $request[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function nameForId(?string $id, string $modelClass, string $nameAttribute, ?Team $team): string
    {
        if ($id === null || $id === '') {
            return '';
        }

        $query = $modelClass::query()->whereKey($id);
        if ($team instanceof Team) {
            $query->where('team_id', $team->getKey());
        }

        return (string) ($query->value($nameAttribute) ?? '');
    }
}
