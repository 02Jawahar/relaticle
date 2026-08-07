<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CreationSource;
use App\Filament\Exports\DealExporter;
use App\Filament\Resources\DealResource\Forms\DealForm;
use App\Filament\Resources\DealResource\Pages\DealsBoard;
use App\Filament\Resources\DealResource\Pages\ListDeals;
use App\Filament\Resources\DealResource\Pages\ViewDeal;
use App\Filament\Resources\DealResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\DealResource\RelationManagers\TasksRelationManager;
use App\Models\Deal;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;
use Relaticle\ActivityLog\Filament\RelationManagers\ActivityLogRelationManager;

final class DealResource extends Resource
{
    protected static ?string $model = Deal::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = null;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return DealForm::get($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('stage')
                    ->label(__('pipelines.fields.stage.label'))
                    ->badge()
                    // The enum exposes a hex accent (also used for board column
                    // headers); Color::hex expands it into the shade array a
                    // Filament badge expects.
                    ->color(fn (Deal $record): array => Color::hex($record->stage->getColor()))
                    ->sortable(),
                TextColumn::make('sub_stage')
                    ->label(__('pipelines.fields.sub_stage.label'))
                    ->placeholder(__('pipelines.fields.sub_stage.empty'))
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label(__('filament/resources/deal.fields.creator.label'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Deal $record): string => $record->created_by),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('creation_source')
                    ->label(__('filament/resources/deal.fields.creation_source.label'))
                    ->options(CreationSource::class)
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(DealExporter::class),
                    RestoreBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
            NotesRelationManager::class,
            ActivityLogRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListDeals::route('/'),
            'board' => DealsBoard::route('/board'),
            'view' => ViewDeal::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('filament/resources/deal.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament/resources/deal.plural_label');
    }

    /**
     * Pipelines open on the board by default; the list stays reachable from the
     * view switcher and keeps its own URL, so existing links still resolve.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function getNavigationUrl(array $parameters = []): string
    {
        return self::getUrl('board', $parameters);
    }

    public static function getNavigationGroup(): string
    {
        return __('filament/panel.navigation_groups.pipelines');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament/resources/deal.navigation_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customFieldValues.customField.options'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
