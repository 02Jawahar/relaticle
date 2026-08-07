<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deal;

use App\Actions\Deal\DeleteDeal;
use App\Mcp\Tools\BaseDeleteTool;
use App\Models\Deal;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete an deal (deal) from the CRM (soft delete).')]
#[IsDestructive]
final class DeleteDealTool extends BaseDeleteTool
{
    protected function modelClass(): string
    {
        return Deal::class;
    }

    protected function actionClass(): string
    {
        return DeleteDeal::class;
    }

    protected function entityLabel(): string
    {
        return 'Deal';
    }
}
