<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Deal;
use App\Observers\Concerns\TagsFirstCrmData;

final readonly class DealObserver
{
    use TagsFirstCrmData;

    /**
     * A sub-stage only exists within its parent stage, so moving a deal to a
     * different stage must drop a sub-stage that no longer applies. Enforced
     * here rather than in a single action because every entry point can move a
     * deal — the board's drag handler, the form, the API and the chat tools.
     */
    public function saving(Deal $deal): void
    {
        if ($deal->sub_stage === null) {
            return;
        }

        if (! in_array($deal->sub_stage, $deal->stage->subStages(), true)) {
            $deal->sub_stage = null;
        }
    }

    public function created(Deal $deal): void
    {
        $this->tagFirstCrmDataIfNeeded($deal);
    }
}
