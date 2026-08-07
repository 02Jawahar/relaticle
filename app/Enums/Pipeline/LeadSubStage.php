<?php

declare(strict_types=1);

namespace App\Enums\Pipeline;

use App\Contracts\Pipeline\PipelineSubStage;

enum LeadSubStage: string implements PipelineSubStage
{
    case WEBSITE = 'website';
    case REFERRAL = 'referral';
    case EVENT = 'event';
    case COLD_OUTREACH = 'cold_outreach';
    case PARTNER = 'partner';
    case IMPORT = 'import';

    case EMAIL_SENT = 'email_sent';
    case WHATSAPP_SENT = 'whatsapp_sent';
    case LINKEDIN = 'linkedin';
    case PHONE_CALL = 'phone_call';

    case DECISION_MAKER_FOUND = 'decision_maker_found';
    case MEETING_SCHEDULED = 'meeting_scheduled';

    case REQUIREMENT_UNDERSTOOD = 'requirement_understood';
    case BUDGET_DISCUSSED = 'budget_discussed';
    case TIMELINE_COLLECTED = 'timeline_collected';

    case FITS_ICP = 'fits_icp';
    case AUTHORITY_VERIFIED = 'authority_verified';
    case BUDGET_APPROVED = 'budget_approved';

    case DEMO_SCHEDULED = 'demo_scheduled';
    case DEMO_COMPLETED = 'demo_completed';

    case EVALUATION_STARTED = 'evaluation_started';
    case TECHNICAL_DISCUSSION = 'technical_discussion';
    case POC_RUNNING = 'poc_running';

    case QUOTATION_SENT = 'quotation_sent';
    case PROPOSAL_SENT = 'proposal_sent';

    case PRICE_DISCUSSION = 'price_discussion';
    case PROCUREMENT_REVIEW = 'procurement_review';
    case TECHNICAL_CLARIFICATION = 'technical_clarification';

    case CONVERTED_TO_DEAL = 'converted_to_deal';

    case LOST_PRICE = 'lost_price';
    case LOST_COMPETITOR = 'lost_competitor';
    case LOST_NO_BUDGET = 'lost_no_budget';
    case LOST_NO_RESPONSE = 'lost_no_response';
    case LOST_TIMING = 'lost_timing';

    public function getLabel(): string
    {
        return __('pipelines.lead.sub_stages.'.$this->value);
    }

    public function stage(): LeadStage
    {
        return match ($this) {
            self::WEBSITE, self::REFERRAL, self::EVENT,
            self::COLD_OUTREACH, self::PARTNER, self::IMPORT => LeadStage::NEW,

            self::EMAIL_SENT, self::WHATSAPP_SENT,
            self::LINKEDIN, self::PHONE_CALL => LeadStage::CONTACTED,

            self::DECISION_MAKER_FOUND, self::MEETING_SCHEDULED => LeadStage::CONNECTED,

            self::REQUIREMENT_UNDERSTOOD, self::BUDGET_DISCUSSED,
            self::TIMELINE_COLLECTED => LeadStage::DISCOVERY,

            self::FITS_ICP, self::AUTHORITY_VERIFIED,
            self::BUDGET_APPROVED => LeadStage::QUALIFIED,

            self::DEMO_SCHEDULED, self::DEMO_COMPLETED => LeadStage::DEMO,

            self::EVALUATION_STARTED, self::TECHNICAL_DISCUSSION,
            self::POC_RUNNING => LeadStage::EVALUATION,

            self::QUOTATION_SENT, self::PROPOSAL_SENT => LeadStage::PROPOSAL,

            self::PRICE_DISCUSSION, self::PROCUREMENT_REVIEW,
            self::TECHNICAL_CLARIFICATION => LeadStage::NEGOTIATION,

            self::CONVERTED_TO_DEAL => LeadStage::WON,

            self::LOST_PRICE, self::LOST_COMPETITOR, self::LOST_NO_BUDGET,
            self::LOST_NO_RESPONSE, self::LOST_TIMING => LeadStage::LOST,
        };
    }
}
