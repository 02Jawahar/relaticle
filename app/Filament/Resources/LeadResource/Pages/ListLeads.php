<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Concerns\HasBoardViewSwitcher;
use App\Filament\Resources\LeadResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Livewire\Attributes\On;
use Override;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;

final class ListLeads extends ListRecords
{
    use HasBoardViewSwitcher;
    use HasResizableColumn;
    use InteractsWithCustomFields;

    protected static string $resource = LeadResource::class;

    /**
     * CSV import and export are not wired up for leads yet, so this page
     * deliberately offers only Create.
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }

    #[On('ai-write-completed')]
    public function refreshOnAiWrite(): void
    {
        // Filament table auto-refreshes on Livewire re-render
    }
}
