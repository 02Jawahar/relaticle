<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\DealResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\SystemAdmin\Filament\Resources\DealResource;

final class CreateDeal extends CreateRecord
{
    protected static string $resource = DealResource::class;
}
