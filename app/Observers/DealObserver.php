<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Deal;
use App\Observers\Concerns\ClearsInvalidSubStage;
use App\Observers\Concerns\TagsFirstCrmData;

final readonly class DealObserver
{
    use ClearsInvalidSubStage;
    use TagsFirstCrmData;

    public function saving(Deal $deal): void
    {
        if (! $this->subStageBelongsToStage($deal->stage, $deal->sub_stage)) {
            $deal->sub_stage = null;
        }
    }

    public function created(Deal $deal): void
    {
        $this->tagFirstCrmDataIfNeeded($deal);
    }
}
