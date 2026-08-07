<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;

/**
 * Stage is deliberately absent: it lives on the deals.stage column alongside
 * sub_stage, because a sub-stage has to be validated against its parent stage
 * and custom-field options cannot express that dependency.
 *
 * @see \App\Enums\Pipeline\DealStage
 */
enum DealField: string
{
    use CustomFieldTrait;

    case AMOUNT = 'amount';
    case CLOSE_DATE = 'close_date';

    public function getFieldType(): string
    {
        return match ($this) {
            self::AMOUNT => CustomFieldType::CURRENCY->value,
            self::CLOSE_DATE => CustomFieldType::DATE->value,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::AMOUNT => 'Amount',
            self::CLOSE_DATE => 'Close Date',
        };
    }

    public function isListToggleableHidden(): bool
    {
        return false;
    }
}
