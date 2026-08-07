<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Deal\ConvertDealToOrder;
use App\Models\Deal;
use App\Models\Order;
use App\Models\User;
use App\Observers\Concerns\ClearsInvalidSubStage;
use App\Observers\Concerns\ConvertsOnWin;
use App\Observers\Concerns\TagsFirstCrmData;

final readonly class DealObserver
{
    use ClearsInvalidSubStage;
    use ConvertsOnWin;
    use TagsFirstCrmData;

    public function saving(Deal $deal): void
    {
        if (! $this->subStageBelongsToStage($deal->stage, $deal->sub_stage)) {
            $deal->sub_stage = null;
        }
    }

    public function created(Deal $deal): void
    {
        $this->tagFirstCrmDataIfNeeded($deal);
    }

    /**
     * A won deal raises an order. As with leads, the action re-saves the deal and
     * the existence check below terminates the recursion.
     */
    public function saved(Deal $deal): void
    {
        if (! $this->justEnteredWonStage($deal)) {
            return;
        }

        if (Order::query()->withoutGlobalScopes()->where('deal_id', $deal->getKey())->exists()) {
            return;
        }

        $this->convertOnWin(
            $deal,
            Order::class,
            fn (User $user): mixed => resolve(ConvertDealToOrder::class)->execute($user, $deal),
        );
    }
}
