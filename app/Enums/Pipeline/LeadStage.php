<?php

declare(strict_types=1);

namespace App\Enums\Pipeline;

use App\Enums\Pipeline\Contracts\PipelineStage;

enum LeadStage: string implements PipelineStage
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case CONNECTED = 'connected';
    case DISCOVERY = 'discovery';
    case QUALIFIED = 'qualified';
    case DEMO = 'demo';
    case EVALUATION = 'evaluation';
    case PROPOSAL = 'proposal';
    case NEGOTIATION = 'negotiation';
    case WON = 'won';
    case LOST = 'lost';

    public function getLabel(): string
    {
        return __('pipelines.lead.stages.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NEW => '#a5b4fc',
            self::CONTACTED => '#818cf8',
            self::CONNECTED => '#6366f1',
            self::DISCOVERY => '#0d9488',
            self::QUALIFIED => '#0891b2',
            self::DEMO => '#eab308',
            self::EVALUATION => '#f59e0b',
            self::PROPOSAL => '#f97316',
            self::NEGOTIATION => '#ea580c',
            self::WON => '#059669',
            self::LOST => '#6b7280',
        };
    }

    /**
     * @return list<LeadSubStage>
     */
    public function subStages(): array
    {
        return match ($this) {
            self::NEW => [
                LeadSubStage::WEBSITE,
                LeadSubStage::REFERRAL,
                LeadSubStage::EVENT,
                LeadSubStage::COLD_OUTREACH,
                LeadSubStage::PARTNER,
                LeadSubStage::IMPORT,
            ],
            self::CONTACTED => [
                LeadSubStage::EMAIL_SENT,
                LeadSubStage::WHATSAPP_SENT,
                LeadSubStage::LINKEDIN,
                LeadSubStage::PHONE_CALL,
            ],
            self::CONNECTED => [
                LeadSubStage::DECISION_MAKER_FOUND,
                LeadSubStage::MEETING_SCHEDULED,
            ],
            self::DISCOVERY => [
                LeadSubStage::REQUIREMENT_UNDERSTOOD,
                LeadSubStage::BUDGET_DISCUSSED,
                LeadSubStage::TIMELINE_COLLECTED,
            ],
            self::QUALIFIED => [
                LeadSubStage::FITS_ICP,
                LeadSubStage::AUTHORITY_VERIFIED,
                LeadSubStage::BUDGET_APPROVED,
            ],
            self::DEMO => [
                LeadSubStage::DEMO_SCHEDULED,
                LeadSubStage::DEMO_COMPLETED,
            ],
            self::EVALUATION => [
                LeadSubStage::EVALUATION_STARTED,
                LeadSubStage::TECHNICAL_DISCUSSION,
                LeadSubStage::POC_RUNNING,
            ],
            self::PROPOSAL => [
                LeadSubStage::QUOTATION_SENT,
                LeadSubStage::PROPOSAL_SENT,
            ],
            self::NEGOTIATION => [
                LeadSubStage::PRICE_DISCUSSION,
                LeadSubStage::PROCUREMENT_REVIEW,
                LeadSubStage::TECHNICAL_CLARIFICATION,
            ],
            self::WON => [
                LeadSubStage::CONVERTED_TO_DEAL,
            ],
            self::LOST => [
                LeadSubStage::LOST_PRICE,
                LeadSubStage::LOST_COMPETITOR,
                LeadSubStage::LOST_NO_BUDGET,
                LeadSubStage::LOST_NO_RESPONSE,
                LeadSubStage::LOST_TIMING,
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
