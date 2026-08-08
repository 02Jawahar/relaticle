<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\Pages;

use App\Actions\Note\CreateNoteForRecord;
use App\Actions\Order\CreateOrder;
use App\Actions\Order\DeleteOrder;
use App\Actions\Order\UpdateOrder;
use App\Actions\Task\CreateTaskForRecord;
use App\Enums\CustomFields\OrderField as OrderCustomField;
use App\Enums\Pipeline\OrderStage;
use App\Filament\Concerns\HasBoardViewSwitcher;
use App\Filament\Infolists\PipelineCardPanel;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Forms\OrderForm;
use App\Models\Order;
use App\Models\Team;
use App\Models\User;
use App\Support\Pipeline\SubStageSelection;
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

final class OrdersBoard extends BoardResourcePage
{
    use HasBoardViewSwitcher;

    protected static string $resource = OrderResource::class;

    /**
     * Boards read left-to-right across every stage, so they use the full
     * viewport instead of Filament's default 7xl content column, which left
     * dead margins either side of the pipeline.
     */
    /**
     * The panel opened by clicking a card. Resolved by name through
     * mountAction('view'), which is why it lives on the page rather than in
     * cardActions() — the board no longer renders a per-card menu, so Edit,
     * Convert and Delete are reached from inside this panel instead.
     */
    public function viewAction(): Action
    {
        return Action::make('view')
            ->label(__('pipelines.card.view'))
            ->icon('heroicon-o-eye')
            ->modalHeading(fn (Order $record): string => $record->name)
            ->schema(PipelineCardPanel::get(...))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('pipelines.card.close'))
            // Styled by .fi-pipeline-card-panel: a right-anchored full-height
            // sheet spanning three quarters of the viewport.
            ->extraModalWindowAttributes(['class' => 'fi-pipeline-card-panel'])
            ->extraModalFooterActions([
                Action::make('edit')
                    ->label(__('filament/pages/boards.orders.actions.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge)
                    ->schema(OrderForm::get(...))
                    ->fillForm(fn (Order $record): array => [
                        'name' => $record->name,
                        'company_id' => $record->company_id,
                        'contact_id' => $record->contact_id,
                        'stage' => $record->stage->value,
                        'sub_stage' => $record->sub_stage?->value,
                    ])
                    ->action(function (Order $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        resolve(UpdateOrder::class)->execute($user, $record, $data);
                    }),
                Action::make('addNote')
                    ->label(__('pipelines.card.add_note'))
                    ->icon('heroicon-o-document-plus')
                    ->color('gray')
                    ->modalWidth(Width::Large)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('pipelines.card.note_title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        resolve(CreateNoteForRecord::class)->execute($user, $record, $data);

                        Notification::make()->title(__('pipelines.card.note_added'))->success()->send();
                    }),
                Action::make('addTask')
                    ->label(__('pipelines.card.add_task'))
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->modalWidth(Width::Large)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('pipelines.card.task_title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('assignee_ids')
                            ->label(__('pipelines.card.task_assignees'))
                            ->multiple()
                            ->searchable()
                            // Plain options, not ->relationship(): the mounted
                            // record is the pipeline record, not the task.
                            ->options(function (): array {
                                $tenant = Filament::getTenant();

                                return $tenant instanceof Team
                                    ? $tenant->allUsers()->pluck('name', 'id')->all()
                                    : [];
                            })
                            ->columnSpanFull(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        resolve(CreateTaskForRecord::class)->execute($user, $record, $data);

                        Notification::make()->title(__('pipelines.card.task_added'))->success()->send();
                    }),
                Action::make('openFullPage')
                    ->label(__('pipelines.card.open_full_page'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', [$record])),
                Action::make('delete')
                    ->label(__('filament/pages/boards.orders.actions.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        resolve(DeleteOrder::class)->execute($user, $record);

                        $this->unmountAction();
                    }),
            ]);
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getTitle(): string
    {
        return __('filament/pages/boards.orders.title');
    }

    public function board(Board $board): Board
    {
        // Resolved here rather than injected: the parent fixes this
        // method's signature, and writes must still go through actions.
        // Update and delete are resolved at their call sites now that those
        // actions moved onto the card panel.
        $createOrder = resolve(CreateOrder::class);

        $customFields = CustomFields::infolist()
            ->forModel(Order::class)
            ->only([OrderCustomField::ORDER_VALUE, OrderCustomField::EXPECTED_DELIVERY_DATE])
            ->hiddenLabels()
            ->visibleWhenFilled()
            ->withoutSections()
            ->values()
            ->keyBy(fn (mixed $field): string => $field->getName());

        return $board
            ->query(
                // Scoped to the tenant explicitly rather than relying on the
                // global scope that ApplyTenantScopes registers per request:
                // a board that silently loses its scope leaks other teams' orders.
                Order::query()
                    ->whereBelongsTo(Filament::getTenant(), 'team')
                    ->with(['company', 'contact'])
            )
            ->recordTitleAttribute('name')
            ->columnIdentifier('stage')
            ->positionIdentifier('order_column')
            ->columns($this->getColumns())
            ->cardSchema(function (Schema $schema) use ($customFields): Schema {
                $amountField = $customFields->get('custom_fields.'.OrderCustomField::ORDER_VALUE->value)
                    ?->visible(fn (?string $state): bool => filled($state))
                    ->badge()
                    ->color('success')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->grow(false)
                    ->hiddenLabel();

                $closeDateField = $customFields->get('custom_fields.'.OrderCustomField::EXPECTED_DELIVERY_DATE->value)
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
                    ->label(__('filament/pages/boards.orders.actions.add'))
                    ->icon('heroicon-o-plus')
                    ->iconButton()
                    ->modalWidth(Width::Large)
                    ->slideOver(false)
                    ->model(Order::class)
                    ->schema(fn (Schema $schema): Schema => $schema
                        ->components([
                            TextInput::make('name')
                                ->required()
                                ->placeholder(__('filament/pages/boards.orders.form.name_placeholder'))
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
                    ->using(function (array $data, CreateAction $action) use ($createOrder): Order {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        $columnId = $action->getArguments()['column'] ?? null;
                        $stage = is_string($columnId) ? OrderStage::tryFrom($columnId) : null;

                        if ($stage instanceof OrderStage) {
                            $data['stage'] = $stage;
                            $data['order_column'] = (float) $this->getBoardPositionInColumn($stage->value);
                        }

                        return $createOrder->execute($user, $data);
                    }),
            ])
            ->cardAction('view')
            // Registered on the board, not as a page method: Flowforge's
            // resolveAction() only looks in the board's own actions, and that is
            // what binds the clicked record. Its dropdown trigger is hidden by
            // CSS because the whole card already opens this panel.
            ->cardActions([$this->viewAction()])
            ->filters([
                SelectFilter::make('companies')
                    ->label(__('filament/pages/boards.orders.filters.company'))
                    ->relationship('company', 'name')
                    ->multiple(),
                SelectFilter::make('contacts')
                    ->label(__('filament/pages/boards.orders.filters.contact'))
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
     * Change a card's sub-stage from the pipeline card panel. Picking the last
     * sub-stage of the current stage advances the card to the next stage column
     * (landing on that stage's first sub-stage); any other pick just records it.
     *
     * Invoked from resources/views/filament/pipeline/sub-stage-select.blade.php.
     */
    public function setSubStage(int|string $recordKey, ?string $subStageValue): void
    {
        $order = Order::query()
            ->whereBelongsTo(Filament::getTenant(), 'team')
            ->find($recordKey);

        abort_unless($order instanceof Order, 404);

        /** @var User $user */
        $user = Auth::guard('web')->user();

        $selection = SubStageSelection::resolve($order->stage, $subStageValue);

        resolve(UpdateOrder::class)->execute($user, $order, $selection->data);

        if ($selection->advanced) {
            $this->dispatch('kanban-card-moved');
        }

        $this->unmountAction();
    }

    /**
     * Columns come from the OrderStage enum, in declaration order.
     *
     * @return Collection<int, array{id: string, name: string, color: string}>
     */
    private function stages(): Collection
    {
        return collect(OrderStage::cases())
            ->map(fn (OrderStage $stage): array => [
                'id' => $stage->value,
                'name' => $stage->getLabel(),
                'color' => $stage->getColor(),
            ]);
    }
}
