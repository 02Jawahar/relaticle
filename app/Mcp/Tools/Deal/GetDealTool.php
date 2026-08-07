<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deal;

use App\Http\Resources\V1\DealResource;
use App\Mcp\Tools\BaseShowTool;
use App\Models\Deal;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Get a single deal by ID with full details and relationships.')]
final class GetDealTool extends BaseShowTool
{
    protected function modelClass(): string
    {
        return Deal::class;
    }

    /** @return class-string<JsonResource> */
    protected function resourceClass(): string
    {
        return DealResource::class;
    }

    protected function entityLabel(): string
    {
        return 'Deal';
    }

    /** @return array<int, string> */
    protected function allowedIncludes(): array
    {
        return ['creator', 'company', 'contact', 'tasksCount', 'notesCount'];
    }
}
