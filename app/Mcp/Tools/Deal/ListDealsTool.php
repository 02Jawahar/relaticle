<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deal;

use App\Actions\Deal\ListDeals;
use App\Http\Resources\V1\DealResource;
use App\Mcp\Tools\BaseListTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List deals (deals) in the CRM with optional search and pagination.')]
#[IsReadOnly]
#[IsIdempotent]
final class ListDealsTool extends BaseListTool
{
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

    protected function additionalSchema(JsonSchema $schema): array
    {
        return [
            'company_id' => $schema->string()->description('Filter by company ID.'),
            'contact_id' => $schema->string()->description('Filter by contact (person) ID.'),
        ];
    }

    protected function additionalFilters(Request $request): array
    {
        return [
            'company_id' => $request->get('company_id'),
            'contact_id' => $request->get('contact_id'),
        ];
    }
}
