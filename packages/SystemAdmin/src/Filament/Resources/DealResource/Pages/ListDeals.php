<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\DealResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;
use Relaticle\SystemAdmin\Filament\Resources\DealResource;

final class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
