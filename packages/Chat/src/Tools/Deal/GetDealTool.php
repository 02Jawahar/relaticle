<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Deal;

use App\Http\Resources\V1\NoteResource;
use App\Http\Resources\V1\DealResource;
use App\Http\Resources\V1\TaskResource;
use App\Models\Deal;
use Illuminate\Http\Resources\Json\JsonResource;
use Relaticle\Chat\Tools\BaseReadShowTool;

final class GetDealTool extends BaseReadShowTool
{
    public function description(): string
    {
        return 'Get a single deal/deal by ID with full details.';
    }

    protected function modelClass(): string
    {
        return Deal::class;
    }

    protected function resourceClass(): string
    {
        return DealResource::class;
    }

    protected function entityLabel(): string
    {
        return 'Deal';
    }

    protected function citationType(): string
    {
        return 'deal';
    }

    /** @return array<string, class-string<JsonResource>> */
    protected function availableIncludes(): array
    {
        return [
            'notes' => NoteResource::class,
            'tasks' => TaskResource::class,
        ];
    }
}
