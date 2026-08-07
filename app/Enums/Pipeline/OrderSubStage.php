<?php

declare(strict_types=1);

namespace App\Enums\Pipeline;

use App\Enums\Pipeline\Contracts\PipelineSubStage;

enum OrderSubStage: string implements PipelineSubStage
{
    case PAYMENT_VERIFIED = 'payment_verified';

    case AVAILABLE = 'available';
    case MANUFACTURING_REQUIRED = 'manufacturing_required';

    case ASSEMBLY = 'assembly';
    case TESTING = 'testing';
    case QC = 'qc';

    case PACKED = 'packed';
    case READY_TO_SHIP = 'ready_to_ship';

    case COURIER_BOOKED = 'courier_booked';
    case IN_TRANSIT = 'in_transit';

    case DELIVERY_CONFIRMED = 'delivery_confirmed';

    case INSTALLATION_SCHEDULED = 'installation_scheduled';
    case INSTALLED = 'installed';

    case TRAINING_SCHEDULED = 'training_scheduled';
    case TRAINING_COMPLETED = 'training_completed';

    case FIRST_FEEDBACK = 'first_feedback';
    case SUPPORT_ACTIVE = 'support_active';

    case WARRANTY_ACTIVE = 'warranty_active';

    case UPSELL = 'upsell';
    case CROSS_SELL = 'cross_sell';
    case AMC = 'amc';

    public function getLabel(): string
    {
        return __('pipelines.order.sub_stages.'.$this->value);
    }

    public function stage(): OrderStage
    {
        return match ($this) {
            self::PAYMENT_VERIFIED => OrderStage::ORDER_RECEIVED,

            self::AVAILABLE, self::MANUFACTURING_REQUIRED => OrderStage::INVENTORY,

            self::ASSEMBLY, self::TESTING, self::QC => OrderStage::PRODUCTION,

            self::PACKED, self::READY_TO_SHIP => OrderStage::PACKAGING,

            self::COURIER_BOOKED, self::IN_TRANSIT => OrderStage::SHIPPING,

            self::DELIVERY_CONFIRMED => OrderStage::DELIVERED,

            self::INSTALLATION_SCHEDULED, self::INSTALLED => OrderStage::INSTALLATION,

            self::TRAINING_SCHEDULED, self::TRAINING_COMPLETED => OrderStage::TRAINING,

            self::FIRST_FEEDBACK, self::SUPPORT_ACTIVE => OrderStage::CUSTOMER_SUCCESS,

            self::WARRANTY_ACTIVE => OrderStage::CLOSED,

            self::UPSELL, self::CROSS_SELL, self::AMC => OrderStage::REPEAT_OPPORTUNITY,
        };
    }
}
