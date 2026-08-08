<?php

declare(strict_types=1);

namespace App\Enums\Pipeline\Concerns;

use App\Contracts\Pipeline\PipelineStage;
use App\Contracts\Pipeline\PipelineSubStage;

/**
 * Shared pipeline-progression logic for the Lead, Deal and Order stage enums.
 *
 * Progression follows enum declaration order, which is the documented pipeline
 * order (see {@see PipelineStage}). A won or lost stage is terminal, so it never
 * advances — this also keeps the "lost" branch, which sits last in declaration
 * order, from ever being reached by advancing forward.
 */
trait ProgressesThroughStages
{
    public function nextStage(): ?PipelineStage
    {
        if ($this->isWon() || $this->isLost()) {
            return null;
        }

        $cases = self::cases();
        $index = array_search($this, $cases, true);

        if ($index === false) {
            return null;
        }

        return $cases[$index + 1] ?? null;
    }

    public function firstSubStage(): ?PipelineSubStage
    {
        return $this->subStages()[0] ?? null;
    }
}
