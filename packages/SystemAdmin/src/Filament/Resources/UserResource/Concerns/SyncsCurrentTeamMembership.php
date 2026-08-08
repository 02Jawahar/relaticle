<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\UserResource\Concerns;

use App\Enums\TeamRole;
use App\Models\User;

/**
 * Keeps a user's team membership in step with the current team assigned
 * through the admin panel.
 *
 * The user form only writes `current_team_id`, which decides where the app
 * redirects a user after login. Filament's tenant access check, however,
 * requires a real membership — an owned team or a `team_user` row. Assigning a
 * current team without a membership sends the user to /app/{slug} and then
 * 404s them out. Creating the missing membership here closes that gap, and
 * because it runs on every save it also self-heals users who were persisted
 * before this fix.
 */
trait SyncsCurrentTeamMembership
{
    protected function syncCurrentTeamMembership(): void
    {
        $user = $this->record;

        if (! $user instanceof User) {
            return;
        }

        $teamId = $user->current_team_id;

        if ($teamId === null || $user->belongsToTeamId((string) $teamId)) {
            return;
        }

        $user->teams()->syncWithoutDetaching([
            $teamId => ['role' => TeamRole::Editor->value],
        ]);
    }
}
