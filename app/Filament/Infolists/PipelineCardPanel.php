<?php

declare(strict_types=1);

namespace App\Filament\Infolists;

use App\Contracts\Pipeline\PipelineStage;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\PeopleResource;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\People;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Relaticle\CustomFields\Facades\CustomFields;

/**
 * The read-only panel shown when a kanban card is clicked, shared by the Lead,
 * Deal and Order boards.
 */
final class PipelineCardPanel
{
    public static function get(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('stage')
                            ->label(__('pipelines.fields.stage.label'))
                            ->badge()
                            ->color(fn (Lead|Deal|Order $record): array => Color::hex(self::stage($record)->getColor())),
                        TextEntry::make('sub_stage')
                            ->label(__('pipelines.fields.sub_stage.label'))
                            ->placeholder(__('pipelines.fields.sub_stage.empty')),
                        TextEntry::make('company.name')
                            ->label(__('pipelines.card.company'))
                            ->placeholder(__('pipelines.fields.sub_stage.empty'))
                            ->color('primary')
                            ->url(fn (Lead|Deal|Order $record): ?string => $record->company instanceof Company
                                ? CompanyResource::getUrl('view', [$record->company])
                                : null),
                        TextEntry::make('contact.name')
                            ->label(__('pipelines.card.contact'))
                            ->placeholder(__('pipelines.fields.sub_stage.empty'))
                            ->color('primary')
                            ->url(fn (Lead|Deal|Order $record): ?string => $record->contact instanceof People
                                ? PeopleResource::getUrl('view', [$record->contact])
                                : null),
                    ]),
                    CustomFields::infolist()->forSchema($schema)->build()->columnSpanFull(),
                    Grid::make(2)->schema([
                        TextEntry::make('created_at')
                            ->label(__('pipelines.card.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('pipelines.card.updated_at'))
                            ->since(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function stage(Lead|Deal|Order $record): PipelineStage
    {
        return $record->stage;
    }
}
