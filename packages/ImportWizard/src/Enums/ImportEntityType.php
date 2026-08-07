<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Enums;

use Relaticle\ImportWizard\Importers\BaseImporter;
use Relaticle\ImportWizard\Importers\CompanyImporter;
use Relaticle\ImportWizard\Importers\DealImporter;
use Relaticle\ImportWizard\Importers\NoteImporter;
use Relaticle\ImportWizard\Importers\PeopleImporter;
use Relaticle\ImportWizard\Importers\TaskImporter;

enum ImportEntityType: string
{
    case Company = 'company';
    case People = 'people';
    case Deal = 'deal';
    case Task = 'task';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Companies',
            self::People => 'People',
            self::Deal => 'Deals',
            self::Task => 'Tasks',
            self::Note => 'Notes',
        };
    }

    public function singular(): string
    {
        return match ($this) {
            self::Company => 'Company',
            self::People => 'Person',
            self::Deal => 'Deal',
            self::Task => 'Task',
            self::Note => 'Note',
        };
    }

    /**
     * Get the importer class for this entity type.
     *
     * @return class-string<BaseImporter>
     */
    public function importerClass(): string
    {
        return match ($this) {
            self::Company => CompanyImporter::class,
            self::People => PeopleImporter::class,
            self::Deal => DealImporter::class,
            self::Task => TaskImporter::class,
            self::Note => NoteImporter::class,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Company => 'heroicon-o-building-office',
            self::People => 'heroicon-o-users',
            self::Deal => 'heroicon-o-currency-dollar',
            self::Task => 'heroicon-o-clipboard-document-check',
            self::Note => 'heroicon-o-document-text',
        };
    }

    public function importer(string $teamId): BaseImporter
    {
        $class = $this->importerClass();

        return new $class($teamId);
    }
}
