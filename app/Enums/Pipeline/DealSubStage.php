<?php

declare(strict_types=1);

namespace App\Enums\Pipeline;

use App\Contracts\Pipeline\PipelineSubStage;

enum DealSubStage: string implements PipelineSubStage
{
    case ASSIGNED_SALESPERSON = 'assigned_salesperson';

    case QUANTITY_FINALIZED = 'quantity_finalized';
    case CUSTOMIZATION_FINALIZED = 'customization_finalized';

    case DISCOUNT_REQUESTED = 'discount_requested';
    case PAYMENT_TERMS = 'payment_terms';
    case DELIVERY_TERMS = 'delivery_terms';

    case SALES_APPROVAL = 'sales_approval';
    case FINANCE_APPROVAL = 'finance_approval';

    case PO_EXPECTED = 'po_expected';
    case VERBAL_CONFIRMATION = 'verbal_confirmation';

    case PO_RECEIVED = 'po_received';
    case PO_VERIFICATION = 'po_verification';

    case PROFORMA_INVOICE = 'proforma_invoice';
    case TAX_INVOICE = 'tax_invoice';

    case ADVANCE_PENDING = 'advance_pending';
    case ADVANCE_RECEIVED = 'advance_received';
    case FULL_PAYMENT_RECEIVED = 'full_payment_received';

    case BOM_LOCKED = 'bom_locked';
    case INVENTORY_CHECKED = 'inventory_checked';

    case MOVE_TO_ORDERS = 'move_to_orders';

    case LOST_CANCELLED = 'lost_cancelled';
    case LOST_COMPETITOR = 'lost_competitor';
    case LOST_BUDGET = 'lost_budget';

    public function getLabel(): string
    {
        return __('pipelines.deal.sub_stages.'.$this->value);
    }

    public function stage(): DealStage
    {
        return match ($this) {
            self::ASSIGNED_SALESPERSON => DealStage::OPPORTUNITY,

            self::QUANTITY_FINALIZED,
            self::CUSTOMIZATION_FINALIZED => DealStage::SOLUTION_FINALIZED,

            self::DISCOUNT_REQUESTED, self::PAYMENT_TERMS,
            self::DELIVERY_TERMS => DealStage::COMMERCIAL_DISCUSSION,

            self::SALES_APPROVAL, self::FINANCE_APPROVAL => DealStage::INTERNAL_APPROVAL,

            self::PO_EXPECTED, self::VERBAL_CONFIRMATION => DealStage::PURCHASE_INTENT,

            self::PO_RECEIVED, self::PO_VERIFICATION => DealStage::PURCHASE_ORDER,

            self::PROFORMA_INVOICE, self::TAX_INVOICE => DealStage::INVOICE,

            self::ADVANCE_PENDING, self::ADVANCE_RECEIVED,
            self::FULL_PAYMENT_RECEIVED => DealStage::PAYMENT,

            self::BOM_LOCKED, self::INVENTORY_CHECKED => DealStage::READY_FOR_PRODUCTION,

            self::MOVE_TO_ORDERS => DealStage::WON,

            self::LOST_CANCELLED, self::LOST_COMPETITOR,
            self::LOST_BUDGET => DealStage::LOST,
        };
    }
}
