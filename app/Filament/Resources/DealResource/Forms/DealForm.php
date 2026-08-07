<?php

declare(strict_types=1);

namespace App\Filament\Resources\DealResource\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Relaticle\CustomFields\Facades\CustomFields;

final class DealForm
{
    public static function get(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->placeholder(__('filament/resources/deal.fields.name.placeholder'))
                    ->columnSpanFull(),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->columnSpan(2),
                Select::make('contact_id')
                    ->relationship('contact', 'name')
                    ->searchable()
                    ->preload()
                    ->columnSpan(2),
                CustomFields::form()->build()->columnSpanFull()->columns(1),
            ])
            ->columns(4);
    }
}
