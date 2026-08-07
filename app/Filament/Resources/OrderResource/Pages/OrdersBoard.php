<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\Pages;

use App\Actions\Order\CreateOrder;
use App\Actions\Order\DeleteOrder;
use App\Actions\Order\UpdateOrder;
use App\Enums\CustomFields\OrderField as OrderCustomField;
use App\Enums\Pipeline\OrderStage;
use App\Filament\Concerns\HasBoardViewSwitcher;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Forms\OrderForm;
use App\Models\Order;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
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

    public function getTitle(): string
    {
        return __('filament/pages/boards.orders.title');
    }

    public function board(Board $board): Board
    {
        // Resolved here rather than injected: the parent fixes this
        // method's signature, and writes must still go through actions.
        $createOrder = app(CreateOrder::class);
        $updateOrder = app(UpdateOrder::class);
        $deleteOrder = app(DeleteOrder::class);

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
            ->searchable(['name'])
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
            ->cardActions([
                Action::make('edit')
                    ->label(__('filament/pages/boards.orders.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(OrderForm::get(...))
                    ->fillForm(fn (Order $record): array => [
                        'name' => $record->name,
                        'company_id' => $record->company_id,
                        'contact_id' => $record->contact_id,
                    ])
                    ->action(function (Order $record, array $data) use ($updateOrder): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        $updateOrder->execute($user, $record, $data);
                    }),
                Action::make('delete')
                    ->label(__('filament/pages/boards.orders.actions.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Order $record) use ($deleteOrder): void {
                        /** @var User $user */
                        $user = Auth::guard('web')->user();

                        $deleteOrder->execute($user, $record);
                    }),
            ])
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
