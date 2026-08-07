<?php

declare(strict_types=1);

namespace App\Filament\Forms;

use App\Contracts\Pipeline\PipelineStage;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * The paired stage / sub-stage selects shared by the Lead, Deal and Order forms.
 *
 * Sub-stage options are derived from the selected stage, which is why these are
 * columns rather than custom fields — custom-field options cannot depend on the
 * value of another field.
 */
final class PipelineStageFields
{
    /**
     * @param  class-string<BackedEnum&PipelineStage>  $stageEnum
     * @return array<int, Component>
     */
    public static function make(string $stageEnum): array
    {
        return [
            Select::make('stage')
                ->label(__('pipelines.fields.stage.label'))
                ->options(self::stageOptions($stageEnum))
                ->required()
                ->native(false)
                ->live()
                // Changing stage invalidates any sub-stage from the old stage.
                // Cleared here so the user never sees a stale pairing; the model
                // observer enforces the same invariant for non-form writes.
                ->afterStateUpdated(fn (Set $set): mixed => $set('sub_stage', null)),

            Select::make('sub_stage')
                ->label(__('pipelines.fields.sub_stage.label'))
                ->placeholder(__('pipelines.fields.sub_stage.placeholder'))
                ->helperText(__('pipelines.fields.sub_stage.helper'))
                ->options(fn (Get $get): array => self::subStageOptions($stageEnum, $get('stage')))
                ->disabled(fn (Get $get): bool => self::resolveStage($stageEnum, $get('stage')) === null)
                ->native(false),
        ];
    }

    /**
     * @param  class-string<BackedEnum&PipelineStage>  $stageEnum
     * @return array<string, string>
     */
    private static function stageOptions(string $stageEnum): array
    {
        $options = [];

        foreach ($stageEnum::cases() as $stage) {
            $options[(string) $stage->value] = $stage->getLabel();
        }

        return $options;
    }

    /**
     * @param  class-string<BackedEnum&PipelineStage>  $stageEnum
     * @return array<string, string>
     */
    private static function subStageOptions(string $stageEnum, mixed $stageValue): array
    {
        $stage = self::resolveStage($stageEnum, $stageValue);

        if ($stage === null) {
            return [];
        }

        $options = [];

        foreach ($stage->subStages() as $subStage) {
            $options[(string) $subStage->value] = $subStage->getLabel();
        }

        return $options;
    }

    /**
     * Accepts either the raw column value or an already-cast enum, because form
     * state holds the string while a hydrated record holds the enum.
     *
     * @param  class-string<BackedEnum&PipelineStage>  $stageEnum
     */
    private static function resolveStage(string $stageEnum, mixed $stageValue): ?PipelineStage
    {
        if ($stageValue instanceof $stageEnum) {
            return $stageValue;
        }

        if (! is_string($stageValue) && ! is_int($stageValue)) {
            return null;
        }

        return $stageEnum::tryFrom($stageValue);
    }
}
