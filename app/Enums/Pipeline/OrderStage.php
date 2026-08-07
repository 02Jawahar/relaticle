<?php

declare(strict_types=1);

namespace App\Enums\Pipeline;

use App\Enums\Pipeline\Contracts\PipelineStage;

enum OrderStage: string implements PipelineStage
{
    case ORDER_RECEIVED = 'order_received';
    case INVENTORY = 'inventory';
    case PRODUCTION = 'production';
    case PACKAGING = 'packaging';
    case SHIPPING = 'shipping';
    case DELIVERED = 'delivered';
    case INSTALLATION = 'installation';
    case TRAINING = 'training';
    case CUSTOMER_SUCCESS = 'customer_success';
    case CLOSED = 'closed';
    case REPEAT_OPPORTUNITY = 'repeat_opportunity';

    public function getLabel(): string
    {
        return __('pipelines.order.stages.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ORDER_RECEIVED => '#a5b4fc',
            self::INVENTORY => '#6366f1',
            self::PRODUCTION => '#7c3aed',
            self::PACKAGING => '#0891b2',
            self::SHIPPING => '#0d9488',
            self::DELIVERED => '#16a34a',
            self::INSTALLATION => '#eab308',
            self::TRAINING => '#f59e0b',
            self::CUSTOMER_SUCCESS => '#059669',
            self::CLOSED => '#64748b',
            self::REPEAT_OPPORTUNITY => '#db2777',
        };
    }

    /**
     * @return list<OrderSubStage>
     */
    public function subStages(): array
    {
        return match ($this) {
            self::ORDER_RECEIVED => [
                OrderSubStage::PAYMENT_VERIFIED,
            ],
            self::INVENTORY => [
                OrderSubStage::AVAILABLE,
                OrderSubStage::MANUFACTURING_REQUIRED,
            ],
            self::PRODUCTION => [
                OrderSubStage::ASSEMBLY,
                OrderSubStage::TESTING,
                OrderSubStage::QC,
            ],
            self::PACKAGING => [
                OrderSubStage::PACKED,
                OrderSubStage::READY_TO_SHIP,
            ],
            self::SHIPPING => [
                OrderSubStage::COURIER_BOOKED,
                OrderSubStage::IN_TRANSIT,
            ],
            self::DELIVERED => [
                OrderSubStage::DELIVERY_CONFIRMED,
            ],
            self::INSTALLATION => [
                OrderSubStage::INSTALLATION_SCHEDULED,
                OrderSubStage::INSTALLED,
            ],
            self::TRAINING => [
                OrderSubStage::TRAINING_SCHEDULED,
                OrderSubStage::TRAINING_COMPLETED,
            ],
            self::CUSTOMER_SUCCESS => [
                OrderSubStage::FIRST_FEEDBACK,
                OrderSubStage::SUPPORT_ACTIVE,
            ],
            self::CLOSED => [
                OrderSubStage::WARRANTY_ACTIVE,
            ],
            self::REPEAT_OPPORTUNITY => [
                OrderSubStage::UPSELL,
                OrderSubStage::CROSS_SELL,
                OrderSubStage::AMC,
            ],
        };
    }

    /**
     * The order pipeline is fulfilment rather than sales: it has no "won" state,
     * so nothing downstream converts out of it. Closed is its terminal success.
     */
    public function isWon(): bool
    {
        return false;
    }

    public function isLost(): bool
    {
        return false;
    }
}
