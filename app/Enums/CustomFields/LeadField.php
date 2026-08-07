<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;
use App\Enums\Pipeline\LeadStage;

/**
 * Stage and sub-stage are columns on leads, not custom fields.
 *
 * @see LeadStage
 */
enum LeadField: string
{
    use CustomFieldTrait;

    case ESTIMATED_VALUE = 'estimated_value';
    case EXPECTED_CLOSE_DATE = 'expected_close_date';

    public function getFieldType(): string
    {
        return match ($this) {
            self::ESTIMATED_VALUE => CustomFieldType::CURRENCY->value,
            self::EXPECTED_CLOSE_DATE => CustomFieldType::DATE->value,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::ESTIMATED_VALUE => 'Estimated Value',
            self::EXPECTED_CLOSE_DATE => 'Expected Close Date',
        };
    }

    public function isListToggleableHidden(): bool
    {
        return false;
    }
}
