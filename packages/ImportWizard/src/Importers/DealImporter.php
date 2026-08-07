<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Importers;

use App\Models\Deal;
use Illuminate\Database\Eloquent\Model;
use Relaticle\ImportWizard\Data\EntityLink;
use Relaticle\ImportWizard\Data\ImportField;
use Relaticle\ImportWizard\Data\ImportFieldCollection;
use Relaticle\ImportWizard\Data\MatchableField;

/**
 * Importer for Deal entities.
 *
 * Deals are linked to companies and contacts.
 * They can only be matched by ID (no attribute-based matching).
 */
final class DealImporter extends BaseImporter
{
    public function modelClass(): string
    {
        return Deal::class;
    }

    public function entityName(): string
    {
        return 'deal';
    }

    public function fields(): ImportFieldCollection
    {
        return new ImportFieldCollection([
            ImportField::id(),

            ImportField::make('name')
                ->label('Name')
                ->required()
                ->rules(['required', 'string', 'max:255'])
                ->guess([
                    'name', 'deal_name', 'deal', 'deal_name',
                    'deal', 'title', 'subject',
                    'deal title', 'deal title', 'pipeline',
                ])
                ->example('Enterprise License Deal')
                ->icon('heroicon-o-currency-dollar'),
        ]);
    }

    /**
     * @return array<string, EntityLink>
     */
    protected function defineEntityLinks(): array
    {
        return [
            'company' => EntityLink::company(),
            'contact' => EntityLink::contact(),
        ];
    }

    /**
     * @return array<MatchableField>
     */
    public function matchableFields(): array
    {
        return [
            MatchableField::id(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  &$context
     * @return array<string, mixed>
     */
    public function prepareForSave(array $data, ?Model $existing, array &$context): array
    {
        $data = parent::prepareForSave($data, $existing, $context);

        if (! $existing instanceof Model) {
            return $this->initializeNewRecordData($data, $context['creator_id'] ?? null);
        }

        return $data;
    }
}
