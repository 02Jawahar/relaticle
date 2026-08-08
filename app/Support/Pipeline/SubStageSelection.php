<?php

declare(strict_types=1);

namespace App\Support\Pipeline;

use App\Contracts\Pipeline\PipelineStage;

/**
 * Resolves what should happen when a user picks a sub-stage from the pipeline
 * card panel.
 *
 * Picking any sub-stage other than the last simply records it. Picking the last
 * sub-stage of a stage is the "stage complete" signal: the card advances to the
 * next stage and lands on that stage's first sub-stage. When the stage is
 * terminal (no next stage) the last sub-stage is recorded like any other.
 */
final readonly class SubStageSelection
{
    /**
     * @param  array<string, mixed>  $data  The attributes to pass to the record's update action.
     */
    private function __construct(
        public array $data,
        public bool $advanced,
    ) {}

    public static function resolve(PipelineStage $stage, ?string $selected): self
    {
        $selected = $selected === '' ? null : $selected;

        $subStages = $stage->subStages();
        $lastSubStage = $subStages === [] ? null : $subStages[array_key_last($subStages)];
        $nextStage = $stage->nextStage();

        $isAdvancing = $selected !== null
            && $lastSubStage !== null
            && $selected === $lastSubStage->value
            && $nextStage instanceof PipelineStage;

        if ($isAdvancing) {
            return new self(
                data: [
                    'stage' => $nextStage->value,
                    'sub_stage' => $nextStage->firstSubStage()?->value,
                ],
                advanced: true,
            );
        }

        return new self(
            data: ['sub_stage' => $selected],
            advanced: false,
        );
    }
}
