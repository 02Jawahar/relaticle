<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Deal;

use App\Actions\Deal\ListDeals;
use App\Http\Resources\V1\DealResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Relaticle\Chat\Tools\BaseReadListTool;

final class ListDealsTool extends BaseReadListTool
{
    public function description(): string
    {
        return 'List deals/deals with optional search and filters.';
    }

    protected function actionClass(): string
    {
        return ListDeals::class;
    }

    protected function resourceClass(): string
    {
        return DealResource::class;
    }

    protected function searchFilterName(): string
    {
        return 'name';
    }

    /** @return array<string, mixed> */
    protected function additionalSchema(JsonSchema $schema): array
    {
        return [
            'company_id' => $schema->string()->description('Filter by company ID.'),
            'contact_id' => $schema->string()->description('Filter by contact/person ID.'),
            'created_after' => $schema->string()->description('Only return records created on or after this date (YYYY-MM-DD).'),
            'created_before' => $schema->string()->description('Only return records created on or before this date (YYYY-MM-DD).'),
            'stale_days' => $schema->integer()->description('Return only deals with no activity in the last N days (default 30). Use this to find deals that have gone quiet.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function additionalFilters(Request $request): array
    {
        return array_filter([
            'company_id' => $request['company_id'] ?? null,
            'contact_id' => $request['contact_id'] ?? null,
            'created_after' => $request['created_after'] ?? null,
            'created_before' => $request['created_before'] ?? null,
            'stale_days' => isset($request['stale_days']) ? (string) $request['stale_days'] : null,
        ]);
    }

    protected function citationType(): string
    {
        return 'deal';
    }
}
