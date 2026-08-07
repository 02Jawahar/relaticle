<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Lead;
use App\Observers\Concerns\ClearsInvalidSubStage;

final readonly class LeadObserver
{
    use ClearsInvalidSubStage;

    public function saving(Lead $lead): void
    {
        if (! $this->subStageBelongsToStage($lead->stage, $lead->sub_stage)) {
            $lead->sub_stage = null;
        }
    }
}
