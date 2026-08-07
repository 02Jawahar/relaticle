<?php

declare(strict_types=1);

namespace App\Observers\Concerns;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Reaching a pipeline's Won stage hands the record to the next pipeline.
 *
 * Kept in the observer rather than behind a button so the transition happens
 * however the record was moved — a kanban drag, the resource form, the API, the
 * chat tools or a seeder. The conversion itself still lives in the action.
 */
trait ConvertsOnWin
{
    /**
     * Whether this save is the moment the record entered its Won stage.
     *
     * A record created directly at Won counts, so imports and seeds convert too.
     */
    private function justEnteredWonStage(Lead|Deal $record): bool
    {
        if (! $record->stage->isWon()) {
            return false;
        }

        return $record->wasRecentlyCreated || $record->wasChanged('stage');
    }

    /**
     * The user the conversion is attributed to.
     *
     * Prefers the authenticated user; falls back to the record's creator and then
     * the team owner so console, queue and seeder paths still work.
     */
    private function actingUserFor(Model $record): ?User
    {
        $user = auth()->user();

        if ($user instanceof User) {
            return $user;
        }

        $creator = $record->getRelationValue('creator');

        if ($creator instanceof User) {
            return $creator;
        }

        $team = $record->getRelationValue('team');

        if (! $team instanceof Team) {
            return null;
        }

        $owner = $team->getRelationValue('owner');

        return $owner instanceof User ? $owner : null;
    }

    /**
     * Runs a conversion, swallowing nothing except the two states that are not
     * failures: no user to attribute it to, and the actor lacking permission.
     *
     * Authorisation is checked up front rather than letting the action's
     * abort_unless() escape, because that would surface as a 403 on whatever
     * write moved the record.
     *
     * @param  class-string<Deal|Order>  $creates
     * @param  callable(User): mixed  $convert
     */
    private function convertOnWin(Lead|Deal $record, string $creates, callable $convert): void
    {
        $user = $this->actingUserFor($record);

        if (! $user instanceof User) {
            return;
        }

        if (! $user->can('update', $record) || ! $user->can('create', $creates)) {
            return;
        }

        try {
            $convert($user);
        } catch (Throwable $e) {
            // A conversion must never take the original save down with it; the
            // record is still correctly marked Won and can be converted by hand.
            report($e);
        }
    }
}
