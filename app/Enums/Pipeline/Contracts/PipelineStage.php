<?php

declare(strict_types=1);

namespace App\Enums\Pipeline\Contracts;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * A stage in one of the CRM pipelines (Lead, Deal, Order).
 *
 * Declaration order of the enum cases is the pipeline order: it drives both the
 * left-to-right kanban column order and the order of options in select fields.
 */
interface PipelineStage extends HasColor, HasLabel
{
    /**
     * The sub-stages selectable while a record sits in this stage.
     *
     * @return list<PipelineSubStage>
     */
    public function subStages(): array;

    /**
     * Whether this stage is a successful terminal state, which is what makes a
     * record eligible for conversion into the next pipeline.
     */
    public function isWon(): bool;

    /**
     * Whether this stage is an unsuccessful terminal state.
     */
    public function isLost(): bool;
}
