<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Deal;

use App\Actions\Deal\DeleteDeal;
use App\Models\Deal;
use Relaticle\Chat\Tools\BaseWriteDeleteTool;

final class DeleteDealTool extends BaseWriteDeleteTool
{
    public function description(): string
    {
        return 'Propose deleting an deal/deal. Returns a proposal for user approval.';
    }

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

    protected function entityType(): string
    {
        return 'deal';
    }
}
