<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Enums\CreationSource;
use App\Enums\Pipeline\DealStage;
use App\Enums\Pipeline\DealSubStage;
use App\Enums\Pipeline\OrderStage;
use App\Models\Deal;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class ConvertDealToOrder
{
    /**
     * Raise an order from a won deal, at the top of the fulfilment pipeline.
     *
     * A deal converts once; the order links back via deal_id.
     */
    public function execute(User $user, Deal $deal): Order
    {
        abort_unless($user->can('update', $deal), 403);
        abort_unless($user->can('create', Order::class), 403);

        $existing = Order::query()->where('deal_id', $deal->getKey())->exists();

        if ($existing) {
            throw new ConflictHttpException(
                __('pipelines.conversion.deal_already_converted', ['name' => $deal->name]),
            );
        }

        return DB::transaction(function () use ($deal): Order {
            $order = Order::query()->create([
                'team_id' => $deal->team_id,
                'deal_id' => $deal->getKey(),
                'company_id' => $deal->company_id,
                'contact_id' => $deal->contact_id,
                'name' => $deal->name,
                'stage' => OrderStage::ORDER_RECEIVED,
                'creation_source' => CreationSource::WEB,
            ]);

            $deal->stage = DealStage::WON;
            $deal->sub_stage = DealSubStage::MOVE_TO_ORDERS;
            $deal->save();

            return $order;
        });
    }
}
