<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\User;

final readonly class DeleteOrder
{
    public function execute(User $user, Order $order): void
    {
        abort_unless($user->can('delete', $order), 403);

        $order->delete();
    }
}
