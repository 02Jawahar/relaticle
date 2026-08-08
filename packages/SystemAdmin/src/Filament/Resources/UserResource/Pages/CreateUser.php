<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\SystemAdmin\Filament\Resources\UserResource;
use Relaticle\SystemAdmin\Filament\Resources\UserResource\Concerns\SyncsCurrentTeamMembership;

final class CreateUser extends CreateRecord
{
    use SyncsCurrentTeamMembership;

    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $this->syncCurrentTeamMembership();
    }
}
