<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\DealResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Override;
use Relaticle\SystemAdmin\Filament\Resources\DealResource;

final class ViewDeal extends ViewRecord
{
    protected static string $resource = DealResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
