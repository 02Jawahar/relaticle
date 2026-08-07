<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Filament\Pages;

use App\Filament\Resources\DealResource;
use Relaticle\ImportWizard\Enums\ImportEntityType;

final class ImportDeals extends ImportPage
{
    protected static ?string $slug = 'deals/import';

    public static function getEntityType(): ImportEntityType
    {
        return ImportEntityType::Deal;
    }

    public static function getResourceClass(): string
    {
        return DealResource::class;
    }
}
