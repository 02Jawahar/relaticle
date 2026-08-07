<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Models\Deal;
use App\Models\User;

final readonly class DeleteDeal
{
    public function execute(User $user, Deal $deal): void
    {
        abort_unless($user->can('delete', $deal), 403);

        $deal->delete();
    }
}
