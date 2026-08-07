<?php

declare(strict_types=1);

namespace App\Filament\Resources\DealResource\Pages;

use App\Actions\Deal\ConvertDealToOrder;
use App\Enums\CustomFields\DealField as DealCustomField;
use App\Enums\Pipeline\DealStage;
use App\Filament\Concerns\HasBoardViewSwitcher;
use App\Filament\Resources\DealResource;
use App\Filament\Resources\DealResource\Forms\DealForm;
use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Relaticle\CustomFields\Facades\CustomFields;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardResourcePage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardFlex;

final class DealsBoard extends BoardResourcePage
{
    use HasBoardViewSwitcher;

    protected static string $resource = DealResource::class;

    public function getTitle(): string
    {
        return __('filament/pages/boards.deals.title');
    }

    public function board(Board $board): Board
    {
        $convertDealToOrder = resolve(ConvertDealToOrder::class);

        $customFields = CustomFields::infolist()
            ->forModel(Deal::class)
            ->only([DealCustomField::AMOUNT, DealCustomField::CLOSE_DATE])
            ->hiddenLabels()
            ->visibleWhenFilled()
            ->withoutSections()
            ->values()
            ->keyBy(fn (mixed $field): string => $field->getName());

        return $board
            ->query(
                // Scoped to the tenant explicitly rather than relying on the
                // global scope that ApplyTenantScopes registers per request:
                // a board that silently loses its scope leaks other teams' deals.
                Deal::query()
                    ->whereBelongsTo(Filament::getTenant(), 'team')
                    ->with(['company', 'contact'])
            )
            ->recordTitleAttribute('name')
            ->columnIdentifier('stage')
            ->positionIdentifier('order_column')
            ->searchable(['name'])
            ->columns($this->getColumns())
            ->cardSchema(function (Schema $schema) use ($customFields): Schema {
                $amountField = $customFields->get('custom_fields.'.DealCustomField::AMOUNT->value)
                    ?->visible(fn (?string $state): bool => filled($state))
                    ->badge()
                    ->color('success')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->grow(false)
                    ->hiddenLabel();

                $closeDateField = $customFields->get('custom_fields.'.DealCustomField::CLOSE_DATE->value)
                    ?->visible(fn (?string $state): bool => filled($state))
                    ->badge()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->grow(false)
                    ->hiddenLabel()
                    ->formatStateUsing(fn (?string $state): string => $this->formatCloseDateBadge($state));

                return $schema
                    ->components([
                        CardFlex::make([
                            TextEntry::make('company.name')
                                ->hiddenLabel()
                                ->visible(fn (?string $state): bool => filled($state))
                                ->icon(Heroicon::OutlinedBuildingOffice)
                                ->color('gray')
                                ->size(TextSize::ExtraSmall)
                                ->grow(),
                        ]),
                        CardFlex::make([
                            $amountField,
                            $closeDateField,
                        ])->align('center'),
                    ]);
            })
            ->columnActions([
                CreateAction::make()
                    ->label(__('filament/pages/boards.deals.actions.add'))
                    ->icon('heroicon-o-plus')
                    ->iconButton()
                    ->modalWidth(Width::Large)
                    ->slideOver(false)
                    ->model(Deal::class)
                    ->schema(fn (Schema $schema): Schema => $schema
                        ->components([
                            TextInput::make('name')
                                ->required()
                                ->placeholder(__('filament/pages/boards.deals.form.name_placeholder'))
                                ->columnSpanFull(),
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('contact_id')
                                ->relationship('contact', 'name')
                                ->searchable()
                                ->preload(),
                            CustomFields::form()
                                ->build()
                                ->columnSpanFull()
                                ->columns(1),
                        ])
                        ->columns(2))
                    ->using(function (array $data, CreateAction $action): Deal {
                        /** @var Team $currentTeam */
                        $currentTeam = Auth::guard('web')->user()->currentTeam;

                        /** @var Deal $deal */
                        $deal = $currentTeam->deals()->create($data);

                        $columnId = $action->getArguments()['column'] ?? null;
                        $stage = is_string($columnId) ? DealStage::tryFrom($columnId) : null;

                        if ($stage instanceof DealStage) {
                            $deal->stage = $stage;
                            $deal->order_column = (float) $this->getBoardPositionInColumn($stage->value);
                            $deal->saveQuietly();
                        }

                        return $deal;
                    }),
            ])
            ->cardActions([
                Action::make('edit')
                    ->label(__('filament/pages/boards.deals.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(DealForm::get(...))
                    ->fillForm(fn (Deal $record): array => [
                        'name' => $record->name,
                        'company_id' => $record->company_id,
                        'contact_id' => $record->contact_id,
                        'stage' => $record->stage->value,
                        'sub_stage' => $record->sub_stage?->value,
                    ])
                    ->action(function (Deal $record, array $data): void {
                        $record->update($data);
                    }),
                Action::make('convert')
                    ->label(__('pipelines.conversion.deal_to_order.label'))
                    ->icon('heroicon-o-cube')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('pipelines.conversion.deal_to_order.heading'))
                    ->modalDescription(__('pipelines.conversion.deal_to_order.description'))
                    ->visible(fn (Deal $record): bool => $record->stage->isWon())
                    ->action(function (Deal $record) use ($convertDealToOrder): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        $convertDealToOrder->execute($user, $record);

                        Notification::make()
                            ->title(__('pipelines.conversion.deal_to_order.success'))
                            ->success()
                            ->send();
                    }),
                Action::make('delete')
                    ->label(__('filament/pages/boards.deals.actions.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Deal $record): void {
                        $record->delete();
                    }),
            ])
            ->filters([
                SelectFilter::make('companies')
                    ->label(__('filament/pages/boards.deals.filters.company'))
                    ->relationship('company', 'name')
                    ->multiple(),
                SelectFilter::make('contacts')
                    ->label(__('filament/pages/boards.deals.filters.contact'))
                    ->relationship('contact', 'name')
                    ->multiple(),
            ])
            ->filtersFormWidth(Width::Medium)
            ->headerToolbar();
    }

    /**
     * Get columns for the board.
     *
     * @return array<Column>
     *
     * @throws Exception
     */
    private function getColumns(): array
    {
        return $this->stages()->map(fn (array $stage): Column => Column::make((string) $stage['id'])
            ->color($stage['color'])
            ->label($stage['name'])
        )->toArray();
    }

    private function formatCloseDateBadge(?string $state): string
    {
        if (blank($state)) {
            return '';
        }

        $date = Date::parse($state);

        return match (true) {
            $date->isPast() => $date->format('M j').' (Overdue)',
            $date->isToday() => 'Closes Today',
            $date->isTomorrow() => 'Closes Tomorrow',
            default => $date->format('M j'),
        };
    }

    /**
     * Columns come from the DealStage enum, in declaration order.
     *
     * @return Collection<int, array{id: string, name: string, color: string}>
     */
    private function stages(): Collection
    {
        return collect(DealStage::cases())
            ->map(fn (DealStage $stage): array => [
                'id' => $stage->value,
                'name' => $stage->getLabel(),
                'color' => $stage->getColor(),
            ]);
    }
}
