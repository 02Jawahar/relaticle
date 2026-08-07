<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\Forms;

use App\Enums\Pipeline\LeadStage;
use App\Filament\Forms\PipelineStageFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Relaticle\CustomFields\Facades\CustomFields;

final class LeadForm
{
    public static function get(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->placeholder(__('filament/resources/lead.fields.name.placeholder'))
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
                ...array_map(
                    fn (Component $component): Component => $component->columnSpan(2),
                    PipelineStageFields::make(LeadStage::class),
                ),
                CustomFields::form()->build()->columnSpanFull()->columns(1),
            ])
            ->columns(4);
    }
}
