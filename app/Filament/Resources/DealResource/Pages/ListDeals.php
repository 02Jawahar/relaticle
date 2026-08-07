<?php

declare(strict_types=1);

namespace App\Filament\Resources\DealResource\Pages;

use App\Filament\Concerns\HasBoardViewSwitcher;
use App\Filament\Exports\DealExporter;
use App\Filament\Resources\DealResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Livewire\Attributes\On;
use Override;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;
use Relaticle\ImportWizard\Filament\Pages\ImportDeals;

final class ListDeals extends ListRecords
{
    use HasBoardViewSwitcher;
    use HasResizableColumn;
    use InteractsWithCustomFields;

    protected static string $resource = DealResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('import')
                    ->label(__('filament/resources/deal.pages.list.actions.import.label'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(ImportDeals::getUrl()),
                ExportAction::make()->exporter(DealExporter::class),
            ])
                ->icon('heroicon-o-arrows-up-down')
                ->color('gray')
                ->button()
                ->label(__('filament/resources/deal.pages.list.actions.import_export.label'))
                ->size(Size::Small),
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }

    #[On('ai-write-completed')]
    public function refreshOnAiWrite(): void
    {
        // Filament table auto-refreshes on Livewire re-render
    }
}
