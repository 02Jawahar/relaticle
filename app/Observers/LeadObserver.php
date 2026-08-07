<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Lead\ConvertLeadToDeal;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Observers\Concerns\ClearsInvalidSubStage;
use App\Observers\Concerns\ConvertsOnWin;

final readonly class LeadObserver
{
    use ClearsInvalidSubStage;
    use ConvertsOnWin;

    public function saving(Lead $lead): void
    {
        if (! $this->subStageBelongsToStage($lead->stage, $lead->sub_stage)) {
            $lead->sub_stage = null;
        }
    }

    /**
     * A won lead becomes a deal. The action re-saves the lead, which re-enters
     * this hook, but the deal now exists so the guard below stops the recursion.
     */
    public function saved(Lead $lead): void
    {
        if (! $this->justEnteredWonStage($lead)) {
            return;
        }

        if (Deal::query()->withoutGlobalScopes()->where('lead_id', $lead->getKey())->exists()) {
            return;
        }

        $this->convertOnWin(
            $lead,
            Deal::class,
            fn (User $user): mixed => resolve(ConvertLeadToDeal::class)->execute($user, $lead),
        );
    }
}
