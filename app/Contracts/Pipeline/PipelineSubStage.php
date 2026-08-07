<?php

declare(strict_types=1);

namespace App\Contracts\Pipeline;

use Filament\Support\Contracts\HasLabel;

/**
 * A sub-stage belonging to exactly one {@see PipelineStage}.
 *
 * Sub-stages are never selectable on their own: the form only offers those
 * returned by the currently selected stage, and writes are validated against
 * the same mapping so the API and chat paths cannot persist a mismatched pair.
 */
interface PipelineSubStage extends HasLabel
{
    /**
     * Narrowed from Filament's HasLabel: a sub-stage always has a label.
     */
    public function getLabel(): string;

    /**
     * The stage this sub-stage belongs to.
     */
    public function stage(): PipelineStage;
}
