<?php

declare(strict_types=1);

namespace App\Enums\Pipeline;

use App\Contracts\Pipeline\PipelineStage;
use App\Enums\Pipeline\Concerns\ProgressesThroughStages;

enum DealStage: string implements PipelineStage
{
    use ProgressesThroughStages;

    case OPPORTUNITY = 'opportunity';
    case SOLUTION_FINALIZED = 'solution_finalized';
    case COMMERCIAL_DISCUSSION = 'commercial_discussion';
    case INTERNAL_APPROVAL = 'internal_approval';
    case PURCHASE_INTENT = 'purchase_intent';
    case PURCHASE_ORDER = 'purchase_order';
    case INVOICE = 'invoice';
    case PAYMENT = 'payment';
    case READY_FOR_PRODUCTION = 'ready_for_production';
    case WON = 'won';
    case LOST = 'lost';

    public function getLabel(): string
    {
        return __('pipelines.deal.stages.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPPORTUNITY => '#a5b4fc',
            self::SOLUTION_FINALIZED => '#6366f1',
            self::COMMERCIAL_DISCUSSION => '#7c3aed',
            self::INTERNAL_APPROVAL => '#9333ea',
            self::PURCHASE_INTENT => '#0891b2',
            self::PURCHASE_ORDER => '#0d9488',
            self::INVOICE => '#eab308',
            self::PAYMENT => '#f59e0b',
            self::READY_FOR_PRODUCTION => '#f97316',
            self::WON => '#059669',
            self::LOST => '#6b7280',
        };
    }

    /**
     * @return list<DealSubStage>
     */
    public function subStages(): array
    {
        return match ($this) {
            self::OPPORTUNITY => [
                DealSubStage::ASSIGNED_SALESPERSON,
            ],
            self::SOLUTION_FINALIZED => [
                DealSubStage::QUANTITY_FINALIZED,
                DealSubStage::CUSTOMIZATION_FINALIZED,
            ],
            self::COMMERCIAL_DISCUSSION => [
                DealSubStage::DISCOUNT_REQUESTED,
                DealSubStage::PAYMENT_TERMS,
                DealSubStage::DELIVERY_TERMS,
            ],
            self::INTERNAL_APPROVAL => [
                DealSubStage::SALES_APPROVAL,
                DealSubStage::FINANCE_APPROVAL,
            ],
            self::PURCHASE_INTENT => [
                DealSubStage::PO_EXPECTED,
                DealSubStage::VERBAL_CONFIRMATION,
            ],
            self::PURCHASE_ORDER => [
                DealSubStage::PO_RECEIVED,
                DealSubStage::PO_VERIFICATION,
            ],
            self::INVOICE => [
                DealSubStage::PROFORMA_INVOICE,
                DealSubStage::TAX_INVOICE,
            ],
            self::PAYMENT => [
                DealSubStage::ADVANCE_PENDING,
                DealSubStage::ADVANCE_RECEIVED,
                DealSubStage::FULL_PAYMENT_RECEIVED,
            ],
            self::READY_FOR_PRODUCTION => [
                DealSubStage::BOM_LOCKED,
                DealSubStage::INVENTORY_CHECKED,
            ],
            self::WON => [
                DealSubStage::MOVE_TO_ORDERS,
            ],
            self::LOST => [
                DealSubStage::LOST_CANCELLED,
                DealSubStage::LOST_COMPETITOR,
                DealSubStage::LOST_BUDGET,
            ],
        };
    }

    public function isWon(): bool
    {
        return $this === self::WON;
    }

    public function isLost(): bool
    {
        return $this === self::LOST;
    }
}
