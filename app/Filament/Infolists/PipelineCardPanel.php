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
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Relaticle\ActivityLog\Filament\Infolists\Components\ActivityLog;
use Relaticle\CustomFields\Facades\CustomFields;

/**
 * The panel shown when a kanban card is clicked, shared by the Lead, Deal and
 * Order boards.
 *
 * Two columns: record detail on the left, and the record's history on the right
 * under tabs, so the panel answers "what is this" and "what has happened" without
 * leaving the board.
 */
final class PipelineCardPanel
{
    public static function get(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                self::details()->columnSpan(1),
                self::history()->columnSpan(2),
            ]);
    }

    private static function details(): Section
    {
        return Section::make()
            ->schema([
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
                CustomFields::infolist()->build()->columnSpanFull(),
                Grid::make(2)->schema([
                    TextEntry::make('created_at')
                        ->label(__('pipelines.card.created_at'))
                        ->dateTime(),
                    TextEntry::make('updated_at')
                        ->label(__('pipelines.card.updated_at'))
                        ->since(),
                ]),
            ]);
    }

    private static function history(): Tabs
    {
        return Tabs::make()
            ->tabs([
                Tab::make(__('pipelines.card.tabs.activity'))
                    ->icon('heroicon-o-clock')
                    ->schema([
                        ActivityLog::make('activity')
                            ->hiddenLabel()
                            ->emptyState(__('pipelines.card.empty.activity'))
                            ->columnSpanFull(),
                    ]),
                Tab::make(__('pipelines.card.tabs.notes'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        RepeatableEntry::make('notes')
                            ->hiddenLabel()
                            ->placeholder(__('pipelines.card.empty.notes'))
                            ->schema([
                                TextEntry::make('title')->hiddenLabel()->weight('semibold'),
                                TextEntry::make('created_at')->hiddenLabel()->since()->color('gray')->size('xs'),
                            ])
                            ->columnSpanFull(),
                    ]),
                Tab::make(__('pipelines.card.tabs.tasks'))
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        RepeatableEntry::make('tasks')
                            ->hiddenLabel()
                            ->placeholder(__('pipelines.card.empty.tasks'))
                            ->schema([
                                TextEntry::make('title')->hiddenLabel()->weight('semibold'),
                                TextEntry::make('created_at')->hiddenLabel()->since()->color('gray')->size('xs'),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function stage(Lead|Deal|Order $record): PipelineStage
    {
        return $record->stage;
    }
}
