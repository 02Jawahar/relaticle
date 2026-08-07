<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;
use App\Enums\Pipeline\OrderStage;

/**
 * Stage and sub-stage are columns on orders, not custom fields.
 *
 * @see OrderStage
 */
enum OrderField: string
{
    use CustomFieldTrait;

    case ORDER_VALUE = 'order_value';
    case PO_NUMBER = 'po_number';
    case EXPECTED_DELIVERY_DATE = 'expected_delivery_date';

    public function getFieldType(): string
    {
        return match ($this) {
            self::ORDER_VALUE => CustomFieldType::CURRENCY->value,
            self::PO_NUMBER => CustomFieldType::TEXT->value,
            self::EXPECTED_DELIVERY_DATE => CustomFieldType::DATE->value,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::ORDER_VALUE => 'Order Value',
            self::PO_NUMBER => 'PO Number',
            self::EXPECTED_DELIVERY_DATE => 'Expected Delivery Date',
        };
    }

    public function isListToggleableHidden(): bool
    {
        return false;
    }
}
