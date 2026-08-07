<?php

declare(strict_types=1);

namespace App\Actions\Lead;

use App\Enums\CreationSource;
use App\Enums\Pipeline\DealStage;
use App\Enums\Pipeline\DealSubStage;
use App\Enums\Pipeline\LeadStage;
use App\Enums\Pipeline\LeadSubStage;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class ConvertLeadToDeal
{
    /**
     * Turn a won lead into a deal at the top of the deal pipeline.
     *
     * The lead is kept rather than deleted: it holds the acquisition history
     * (source sub-stage, notes, tasks) that the deal has no place for, and the
     * new deal links back to it via lead_id.
     */
    public function execute(User $user, Lead $lead): Deal
    {
        abort_unless($user->can('update', $lead), 403);
        abort_unless($user->can('create', Deal::class), 403);

        // A relation query rather than $lead->deal: the attribute would lazy load,
        // which this app disables, and the action must work from the observer and
        // console paths too, not only where a caller remembered to eager load.
        $alreadyConverted = Deal::query()
            ->withoutGlobalScopes()
            ->where('lead_id', $lead->getKey())
            ->exists();

        if ($alreadyConverted) {
            throw new ConflictHttpException(
                __('pipelines.conversion.lead_already_converted', ['name' => $lead->name]),
            );
        }

        return DB::transaction(function () use ($lead): Deal {
            $deal = Deal::query()->create([
                'team_id' => $lead->team_id,
                'lead_id' => $lead->getKey(),
                'company_id' => $lead->company_id,
                'contact_id' => $lead->contact_id,
                'name' => $lead->name,
                'stage' => DealStage::OPPORTUNITY,
                'sub_stage' => DealSubStage::ASSIGNED_SALESPERSON,
                'creation_source' => CreationSource::WEB,
            ]);

            // Record the outcome on the lead so the board reflects the handover.
            $lead->stage = LeadStage::WON;
            $lead->sub_stage = LeadSubStage::CONVERTED_TO_DEAL;
            $lead->save();

            return $deal;
        });
    }
}
