<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * SOX control point identifiers for the procurement process.
 *
 * Each control point maps to a specific validation checkpoint
 * in the P2P workflow that must pass for SOX compliance.
 */
enum SOXControlPoint: string
{
    // ===== Requisition Controls =====
    /**
     * Validates requisition has proper authorization before processing.
     */
    case REQ_AUTHORIZATION = 'sox.req.authorization';

    /**
     * Validates budget availability before requisition approval.
     */
    case REQ_BUDGET_CHECK = 'sox.req.budget_check';

    /**
     * Validates requisition adheres to spend policies.
     */
    case REQ_SPEND_POLICY = 'sox.req.spend_policy';

    // ===== Purchase Order Controls =====
    /**
     * Validates PO amount against user authorization limits.
     */
    case PO_AMOUNT_LIMIT = 'sox.po.amount_limit';

    /**
     * Validates vendor is approved and not blocked.
     */
    case PO_VENDOR_APPROVED = 'sox.po.vendor_approved';

    /**
     * Validates PO pricing matches contract terms.
     */
    case PO_CONTRACT_PRICING = 'sox.po.contract_pricing';

    /**
     * Validates PO modifications have proper approvals.
     */
    case PO_CHANGE_APPROVAL = 'sox.po.change_approval';

    // ===== Goods Receipt Controls =====
    /**
     * Validates goods receipt quantity against PO tolerance.
     */
    case GR_QUANTITY_TOLERANCE = 'sox.gr.quantity_tolerance';

    /**
     * Validates segregation between receiver and PO creator.
     */
    case GR_SEGREGATION = 'sox.gr.segregation';

    /**
     * Validates quality inspection requirements met.
     */
    case GR_QUALITY_INSPECTION = 'sox.gr.quality_inspection';

    // ===== Invoice Controls =====
    /**
     * Validates invoice against PO and GR (three-way match).
     */
    case INV_THREE_WAY_MATCH = 'sox.inv.three_way_match';

    /**
     * Detects duplicate invoice submissions.
     */
    case INV_DUPLICATE_CHECK = 'sox.inv.duplicate_check';

    /**
     * Validates tax amounts and codes on invoice.
     */
    case INV_TAX_VALIDATION = 'sox.inv.tax_validation';

    /**
     * Validates invoice price variance within tolerance.
     */
    case INV_PRICE_VARIANCE = 'sox.inv.price_variance';

    /**
     * Validates invoice approval hierarchy.
     */
    case INV_APPROVAL_HIERARCHY = 'sox.inv.approval_hierarchy';

    // ===== Payment Controls =====
    /**
     * Validates payment terms are met before payment.
     */
    case PAY_TERMS_MET = 'sox.pay.terms_met';

    /**
     * Validates payment amount against approved invoice.
     */
    case PAY_AMOUNT_MATCH = 'sox.pay.amount_match';

    /**
     * Detects duplicate payment attempts.
     */
    case PAY_DUPLICATE_CHECK = 'sox.pay.duplicate_check';

    /**
     * Validates vendor bank account is verified.
     */
    case PAY_BANK_VERIFIED = 'sox.pay.bank_verified';

    /**
     * Validates segregation between approver and payer.
     */
    case PAY_SEGREGATION = 'sox.pay.segregation';

    /**
     * Validates payment approval based on amount thresholds.
     */
    case PAY_APPROVAL_THRESHOLD = 'sox.pay.approval_threshold';

    // ===== Vendor Controls =====
    /**
     * Validates vendor compliance status before transactions.
     */
    case VENDOR_COMPLIANCE = 'sox.vendor.compliance';

    /**
     * Validates vendor is not on sanctions lists.
     */
    case VENDOR_SANCTIONS = 'sox.vendor.sanctions';

    /**
     * Validates vendor master data changes are approved.
     */
    case VENDOR_MASTER_CHANGE = 'sox.vendor.master_change';

    // ===== Segregation of Duties Controls =====
    /**
     * Validates requestor is not the same as approver.
     */
    case SOD_REQUESTOR_APPROVER = 'sox.sod.requestor_approver';

    /**
     * Validates PO creator is different from invoice matcher.
     */
    case SOD_PO_CREATOR_MATCHER = 'sox.sod.po_creator_matcher';

    /**
     * Validates goods receiver is different from payer.
     */
    case SOD_RECEIVER_PAYER = 'sox.sod.receiver_payer';

    /**
     * Validates vendor creator is different from payer.
     */
    case SOD_VENDOR_PAYER = 'sox.sod.vendor_payer';

    /**
     * Get the SOX control type for this control point.
     */
    public function getControlType(): SOXControlType
    {
        return match ($this) {
            // Preventive controls
            self::REQ_AUTHORIZATION,
            self::REQ_BUDGET_CHECK,
            self::REQ_SPEND_POLICY,
            self::PO_AMOUNT_LIMIT,
            self::PO_VENDOR_APPROVED,
            self::PO_CONTRACT_PRICING,
            self::PO_CHANGE_APPROVAL,
            self::GR_SEGREGATION,
            self::PAY_SEGREGATION,
            self::SOD_REQUESTOR_APPROVER,
            self::SOD_PO_CREATOR_MATCHER,
            self::SOD_RECEIVER_PAYER,
            self::SOD_VENDOR_PAYER => SOXControlType::PREVENTIVE,

            // Detective controls
            self::INV_DUPLICATE_CHECK,
            self::PAY_DUPLICATE_CHECK,
            self::VENDOR_SANCTIONS => SOXControlType::DETECTIVE,

            // Application controls (automated)
            self::GR_QUANTITY_TOLERANCE,
            self::INV_THREE_WAY_MATCH,
            self::INV_PRICE_VARIANCE,
            self::INV_TAX_VALIDATION,
            self::PAY_TERMS_MET,
            self::PAY_AMOUNT_MATCH,
            self::PAY_BANK_VERIFIED,
            self::PAY_APPROVAL_THRESHOLD => SOXControlType::APPLICATION,

            // Hybrid controls
            self::GR_QUALITY_INSPECTION,
            self::INV_APPROVAL_HIERARCHY,
            self::VENDOR_COMPLIANCE,
            self::VENDOR_MASTER_CHANGE => SOXControlType::HYBRID,
        };
    }

    /**
     * Get the risk level if this control fails (1-5, 5 being highest).
     */
    public function getRiskLevel(): int
    {
        return match ($this) {
            // Critical risk (5)
            self::PAY_DUPLICATE_CHECK,
            self::INV_DUPLICATE_CHECK,
            self::VENDOR_SANCTIONS,
            self::PAY_BANK_VERIFIED => 5,

            // High risk (4)
            self::SOD_REQUESTOR_APPROVER,
            self::SOD_PO_CREATOR_MATCHER,
            self::SOD_RECEIVER_PAYER,
            self::SOD_VENDOR_PAYER,
            self::PAY_SEGREGATION,
            self::PAY_AMOUNT_MATCH,
            self::PAY_APPROVAL_THRESHOLD => 4,

            // Medium-high risk (3)
            self::REQ_AUTHORIZATION,
            self::PO_AMOUNT_LIMIT,
            self::PO_VENDOR_APPROVED,
            self::INV_THREE_WAY_MATCH,
            self::VENDOR_COMPLIANCE,
            self::VENDOR_MASTER_CHANGE => 3,

            // Medium risk (2)
            self::REQ_BUDGET_CHECK,
            self::REQ_SPEND_POLICY,
            self::PO_CONTRACT_PRICING,
            self::PO_CHANGE_APPROVAL,
            self::GR_SEGREGATION,
            self::INV_TAX_VALIDATION,
            self::INV_PRICE_VARIANCE,
            self::INV_APPROVAL_HIERARCHY,
            self::PAY_TERMS_MET => 2,

            // Lower risk (1)
            self::GR_QUANTITY_TOLERANCE,
            self::GR_QUALITY_INSPECTION => 1,
        };
    }

    /**
     * Get the P2P step this control applies to.
     */
    public function getP2PStep(): P2PStep
    {
        return match ($this) {
            self::REQ_AUTHORIZATION,
            self::REQ_BUDGET_CHECK,
            self::REQ_SPEND_POLICY,
            self::SOD_REQUESTOR_APPROVER => P2PStep::REQUISITION,

            self::PO_AMOUNT_LIMIT,
            self::PO_VENDOR_APPROVED,
            self::PO_CONTRACT_PRICING,
            self::PO_CHANGE_APPROVAL,
            self::SOD_PO_CREATOR_MATCHER => P2PStep::PO_CREATION,

            self::GR_QUANTITY_TOLERANCE,
            self::GR_SEGREGATION,
            self::GR_QUALITY_INSPECTION,
            self::SOD_RECEIVER_PAYER => P2PStep::GOODS_RECEIPT,

            self::INV_THREE_WAY_MATCH,
            self::INV_DUPLICATE_CHECK,
            self::INV_TAX_VALIDATION,
            self::INV_PRICE_VARIANCE,
            self::INV_APPROVAL_HIERARCHY => P2PStep::INVOICE_MATCH,

            self::PAY_TERMS_MET,
            self::PAY_AMOUNT_MATCH,
            self::PAY_DUPLICATE_CHECK,
            self::PAY_BANK_VERIFIED,
            self::PAY_SEGREGATION,
            self::PAY_APPROVAL_THRESHOLD,
            self::SOD_VENDOR_PAYER => P2PStep::PAYMENT,

            self::VENDOR_COMPLIANCE,
            self::VENDOR_SANCTIONS,
            self::VENDOR_MASTER_CHANGE => P2PStep::PO_CREATION, // Vendor checks during PO
        };
    }

    /**
     * Get all controls for a specific P2P step.
     *
     * @return array<self>
     */
    public static function forStep(P2PStep $step): array
    {
        return array_filter(
            self::cases(),
            fn (self $control) => $control->getP2PStep() === $step
        );
    }

    /**
     * Get all controls with a specific risk level or higher.
     *
     * @return array<self>
     */
    public static function withMinimumRisk(int $level): array
    {
        return array_filter(
            self::cases(),
            fn (self $control) => $control->getRiskLevel() >= $level
        );
    }

    /**
     * Get a human-readable description of the control.
     */
    public function description(): string
    {
        return match ($this) {
            self::REQ_AUTHORIZATION => 'Requisition has proper authorization',
            self::REQ_BUDGET_CHECK => 'Budget availability verified',
            self::REQ_SPEND_POLICY => 'Spend policy compliance checked',
            self::PO_AMOUNT_LIMIT => 'PO amount within user limits',
            self::PO_VENDOR_APPROVED => 'Vendor is approved and not blocked',
            self::PO_CONTRACT_PRICING => 'Pricing matches contract terms',
            self::PO_CHANGE_APPROVAL => 'PO changes properly approved',
            self::GR_QUANTITY_TOLERANCE => 'Receipt quantity within tolerance',
            self::GR_SEGREGATION => 'Receiver segregated from PO creator',
            self::GR_QUALITY_INSPECTION => 'Quality inspection completed',
            self::INV_THREE_WAY_MATCH => 'Three-way match passed',
            self::INV_DUPLICATE_CHECK => 'No duplicate invoice detected',
            self::INV_TAX_VALIDATION => 'Tax amounts and codes validated',
            self::INV_PRICE_VARIANCE => 'Price variance within tolerance',
            self::INV_APPROVAL_HIERARCHY => 'Invoice approval hierarchy followed',
            self::PAY_TERMS_MET => 'Payment terms satisfied',
            self::PAY_AMOUNT_MATCH => 'Payment matches approved amount',
            self::PAY_DUPLICATE_CHECK => 'No duplicate payment detected',
            self::PAY_BANK_VERIFIED => 'Vendor bank account verified',
            self::PAY_SEGREGATION => 'Payer segregated from approver',
            self::PAY_APPROVAL_THRESHOLD => 'Payment approval threshold met',
            self::VENDOR_COMPLIANCE => 'Vendor compliance verified',
            self::VENDOR_SANCTIONS => 'Vendor not on sanctions list',
            self::VENDOR_MASTER_CHANGE => 'Vendor changes properly approved',
            self::SOD_REQUESTOR_APPROVER => 'Requestor not same as approver',
            self::SOD_PO_CREATOR_MATCHER => 'PO creator not same as matcher',
            self::SOD_RECEIVER_PAYER => 'Receiver not same as payer',
            self::SOD_VENDOR_PAYER => 'Vendor creator not same as payer',
        };
    }
}
