<?php

declare(strict_types=1);

namespace App\Contracts\Pipeline;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * A stage in one of the CRM pipelines (Lead, Deal, Order).
 *
 * Declaration order of the enum cases is the pipeline order: it drives both the
 * left-to-right kanban column order and the order of options in select fields,
 * and is what {@see self::nextStage()} walks when a card advances.
 */
interface PipelineStage extends BackedEnum, HasColor, HasLabel
{
    /**
     * Narrowed from Filament's HasLabel: a stage always has a label.
     */
    public function getLabel(): string;

    /**
     * The accent colour as a hex string. Used directly for kanban column
     * headers, and expanded via Color::hex() where Filament wants a shade array.
     */
    public function getColor(): string;

    /**
     * The sub-stages selectable while a record sits in this stage.
     *
     * @return list<PipelineSubStage&BackedEnum>
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

    /**
     * The stage a card advances to when it completes this stage, or null when
     * this stage is terminal (won/lost) or the pipeline has no further stage.
     */
    public function nextStage(): ?PipelineStage;

    /**
     * The first sub-stage of this stage, used as the entry sub-stage when a card
     * advances into it. Null when the stage declares no sub-stages.
     */
    public function firstSubStage(): ?PipelineSubStage;
}
