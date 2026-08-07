<?php

declare(strict_types=1);

namespace App\Observers\Concerns;

use App\Contracts\Pipeline\PipelineStage;
use App\Contracts\Pipeline\PipelineSubStage;

/**
 * A sub-stage only exists within its parent stage, so moving a record to a
 * different stage must drop a sub-stage that no longer applies.
 *
 * Lives in the observers rather than in a single action because every entry
 * point can change a stage — the kanban drag handler, the resource form, the
 * REST API and the chat tools — and the pairing must never be persisted broken.
 */
trait ClearsInvalidSubStage
{
    private function subStageBelongsToStage(PipelineStage $stage, ?PipelineSubStage $subStage): bool
    {
        if ($subStage === null) {
            return true;
        }

        return in_array($subStage, $stage->subStages(), true);
    }
}
