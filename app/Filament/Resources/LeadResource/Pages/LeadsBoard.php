<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\Pages;

use App\Actions\Lead\ConvertLeadToDeal;
use App\Actions\Lead\CreateLead;
use App\Actions\Lead\DeleteLead;
use App\Actions\Lead\UpdateLead;
use App\Enums\CustomFields\LeadField as LeadCustomField;
use App\Enums\Pipeline\LeadStage;
use App\Filament\Concerns\HasBoardViewSwitcher;
use App\Filament\Infolists\PipelineCardPanel;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\LeadResource\Forms\LeadForm;
use App\Models\Lead;
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

final class LeadsBoard extends BoardResourcePage
{
    use HasBoardViewSwitcher;

    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return __('filament/pages/boards.leads.title');
    }

    public function board(Board $board): Board
    {
        // Resolved here rather than injected: the parent fixes this
        // method's signature, and writes must still go through actions.
        $createLead = resolve(CreateLead::class);
        $updateLead = resolve(UpdateLead::class);
        $deleteLead = resolve(DeleteLead::class);
        $convertLeadToDeal = resolve(ConvertLeadToDeal::class);

        $customFields = CustomFields::infolist()
            ->forModel(Lead::class)
            ->only([LeadCustomField::ESTIMATED_VALUE, LeadCustomField::EXPECTED_CLOSE_DATE])
            ->hiddenLabels()
            ->visibleWhenFilled()
            ->withoutSections()
            ->values()
            ->keyBy(fn (mixed $field): string => $field->getName());

        return $board
            ->query(
                // Scoped to the tenant explicitly rather than relying on the
                // global scope that ApplyTenantScopes registers per request:
                // a board that silently loses its scope leaks other teams' leads.
                Lead::query()
                    ->whereBelongsTo(Filament::getTenant(), 'team')
                    ->with(['company', 'contact'])
            )
            ->recordTitleAttribute('name')
            ->columnIdentifier('stage')
            ->positionIdentifier('order_column')
            ->searchable(['name'])
            ->columns($this->getColumns())
            ->cardSchema(function (Schema $schema) use ($customFields): Schema {
                $amountField = $customFields->get('custom_fields.'.LeadCustomField::ESTIMATED_VALUE->value)
                    ?->visible(fn (?string $state): bool => filled($state))
                    ->badge()
                    ->color('success')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->grow(false)
                    ->hiddenLabel();

                $closeDateField = $customFields->get('custom_fields.'.LeadCustomField::EXPECTED_CLOSE_DATE->value)
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
                    ->label(__('filament/pages/boards.leads.actions.add'))
                    ->icon('heroicon-o-plus')
                    ->iconButton()
                    ->modalWidth(Width::Large)
                    ->slideOver(false)
                    ->model(Lead::class)
                    ->schema(fn (Schema $schema): Schema => $schema
                        ->components([
                            TextInput::make('name')
                                ->required()
                                ->placeholder(__('filament/pages/boards.leads.form.name_placeholder'))
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
                    ->using(function (array $data, CreateAction $action) use ($createLead): Lead {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        $columnId = $action->getArguments()['column'] ?? null;
                        $stage = is_string($columnId) ? LeadStage::tryFrom($columnId) : null;

                        if ($stage instanceof LeadStage) {
                            $data['stage'] = $stage;
                            $data['order_column'] = (float) $this->getBoardPositionInColumn($stage->value);
                        }

                        return $createLead->execute($user, $data);
                    }),
            ])
            ->cardAction('view')
            ->cardActions([
                Action::make('view')
                    ->label(__('pipelines.card.view'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Lead $record): string => $record->name)
                    ->schema(PipelineCardPanel::get(...))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('pipelines.card.close'))
                    // Styled by .fi-pipeline-card-panel: a left-anchored
                    // full-height panel spanning three quarters of the viewport.
                    ->extraModalWindowAttributes(['class' => 'fi-pipeline-card-panel'])
                    ->extraModalFooterActions([
                        Action::make('openFullPage')
                            ->label(__('pipelines.card.open_full_page'))
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->color('gray')
                            ->url(fn (Lead $record): string => LeadResource::getUrl('view', [$record])),
                    ]),
                Action::make('edit')
                    ->label(__('filament/pages/boards.leads.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(LeadForm::get(...))
                    ->fillForm(fn (Lead $record): array => [
                        'name' => $record->name,
                        'company_id' => $record->company_id,
                        'contact_id' => $record->contact_id,
                        'stage' => $record->stage->value,
                        'sub_stage' => $record->sub_stage?->value,
                    ])
                    ->action(function (Lead $record, array $data) use ($updateLead): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        $updateLead->execute($user, $record, $data);
                    }),
                Action::make('convert')
                    ->label(__('pipelines.conversion.lead_to_deal.label'))
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('pipelines.conversion.lead_to_deal.heading'))
                    ->modalDescription(__('pipelines.conversion.lead_to_deal.description'))
                    ->visible(fn (Lead $record): bool => $record->stage->isWon())
                    ->action(function (Lead $record) use ($convertLeadToDeal): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        $convertLeadToDeal->execute($user, $record);

                        Notification::make()
                            ->title(__('pipelines.conversion.lead_to_deal.success'))
                            ->success()
                            ->send();
                    }),
                Action::make('delete')
                    ->label(__('filament/pages/boards.leads.actions.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Lead $record) use ($deleteLead): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        $deleteLead->execute($user, $record);
                    }),
            ])
            ->filters([
                SelectFilter::make('companies')
                    ->label(__('filament/pages/boards.leads.filters.company'))
                    ->relationship('company', 'name')
                    ->multiple(),
                SelectFilter::make('contacts')
                    ->label(__('filament/pages/boards.leads.filters.contact'))
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
     * Columns come from the LeadStage enum, in declaration order.
     *
     * @return Collection<int, array{id: string, name: string, color: string}>
     */
    private function stages(): Collection
    {
        return collect(LeadStage::cases())
            ->map(fn (LeadStage $stage): array => [
                'id' => $stage->value,
                'name' => $stage->getLabel(),
                'color' => $stage->getColor(),
            ]);
    }
}
