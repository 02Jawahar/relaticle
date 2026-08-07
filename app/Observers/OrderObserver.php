<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Order;
use App\Observers\Concerns\ClearsInvalidSubStage;

final readonly class OrderObserver
{
    use ClearsInvalidSubStage;

    public function saving(Order $order): void
    {
        if (! $this->subStageBelongsToStage($order->stage, $order->sub_stage)) {
            $order->sub_stage = null;
        }
    }
}
