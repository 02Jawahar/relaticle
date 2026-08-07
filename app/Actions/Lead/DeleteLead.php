<?php

declare(strict_types=1);

namespace App\Actions\Lead;

use App\Models\Lead;
use App\Models\User;

final readonly class DeleteLead
{
    public function execute(User $user, Lead $lead): void
    {
        abort_unless($user->can('delete', $lead), 403);

        $lead->delete();
    }
}
