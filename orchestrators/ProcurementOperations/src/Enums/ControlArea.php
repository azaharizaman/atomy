<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * SOX internal control areas for procurement audit.
 *
 * Maps to COSO framework components and PCAOB standards.
 */
enum ControlArea: string
{
    case PURCHASE_REQUISITION = 'purchase_requisition';
    case PURCHASE_ORDER_APPROVAL = 'purchase_order_approval';
    case GOODS_RECEIPT = 'goods_receipt';
    case INVOICE_PROCESSING = 'invoice_processing';
    case THREE_WAY_MATCH = 'three_way_match';
    case PAYMENT_AUTHORIZATION = 'payment_authorization';
    case VENDOR_MASTER = 'vendor_master';
    case SEGREGATION_OF_DUTIES = 'segregation_of_duties';
    case APPROVAL_LIMITS = 'approval_limits';
    case PERIOD_END_CUTOFF = 'period_end_cutoff';
    case ACCRUAL_RECOGNITION = 'accrual_recognition';
    case INTERCOMPANY_TRANSACTIONS = 'intercompany_transactions';

    /**
     * Get control objective description.
     */
    public function getObjective(): string
    {
        return match ($this) {
            self::PURCHASE_REQUISITION => 'Ensure all purchases are properly authorized and necessary for business operations.',
            self::PURCHASE_ORDER_APPROVAL => 'Ensure POs are approved by authorized personnel within established limits.',
            self::GOODS_RECEIPT => 'Ensure goods received match PO specifications and are properly recorded.',
            self::INVOICE_PROCESSING => 'Ensure invoices are legitimate, accurate, and processed timely.',
            self::THREE_WAY_MATCH => 'Ensure payments are made only for goods/services ordered and received.',
            self::PAYMENT_AUTHORIZATION => 'Ensure payments are authorized by appropriate personnel.',
            self::VENDOR_MASTER => 'Ensure vendor data integrity and prevent fraudulent vendors.',
            self::SEGREGATION_OF_DUTIES => 'Ensure no single individual can complete a transaction from initiation to payment.',
            self::APPROVAL_LIMITS => 'Ensure approval thresholds are appropriate and enforced.',
            self::PERIOD_END_CUTOFF => 'Ensure transactions are recorded in the correct accounting period.',
            self::ACCRUAL_RECOGNITION => 'Ensure liabilities are properly accrued for goods/services received but not invoiced.',
            self::INTERCOMPANY_TRANSACTIONS => 'Ensure intercompany transactions are properly recorded and eliminated.',
        };
    }

    /**
     * Get typical test sample size.
     */
    public function getTestSampleSize(): int
    {
        return match ($this) {
            self::THREE_WAY_MATCH,
            self::PAYMENT_AUTHORIZATION => 40,
            self::PURCHASE_ORDER_APPROVAL,
            self::INVOICE_PROCESSING => 25,
            self::GOODS_RECEIPT,
            self::PERIOD_END_CUTOFF => 20,
            self::VENDOR_MASTER,
            self::SEGREGATION_OF_DUTIES,
            self::APPROVAL_LIMITS => 15,
            self::ACCRUAL_RECOGNITION,
            self::INTERCOMPANY_TRANSACTIONS => 10,
            self::PURCHASE_REQUISITION => 25,
        };
    }

    /**
     * Get COSO framework component mapping.
     */
    public function getCOSOComponent(): string
    {
        return match ($this) {
            self::PURCHASE_REQUISITION,
            self::PURCHASE_ORDER_APPROVAL,
            self::PAYMENT_AUTHORIZATION => 'Control Activities',
            self::GOODS_RECEIPT,
            self::INVOICE_PROCESSING,
            self::THREE_WAY_MATCH => 'Control Activities',
            self::VENDOR_MASTER => 'Risk Assessment',
            self::SEGREGATION_OF_DUTIES,
            self::APPROVAL_LIMITS => 'Control Environment',
            self::PERIOD_END_CUTOFF,
            self::ACCRUAL_RECOGNITION => 'Information & Communication',
            self::INTERCOMPANY_TRANSACTIONS => 'Monitoring Activities',
        };
    }

    /**
     * Get related financial statement assertions.
     *
     * @return array<string>
     */
    public function getRelatedAssertions(): array
    {
        return match ($this) {
            self::PURCHASE_REQUISITION,
            self::PURCHASE_ORDER_APPROVAL => ['Authorization', 'Occurrence'],
            self::GOODS_RECEIPT => ['Completeness', 'Accuracy'],
            self::INVOICE_PROCESSING => ['Accuracy', 'Validity'],
            self::THREE_WAY_MATCH => ['Validity', 'Accuracy', 'Authorization'],
            self::PAYMENT_AUTHORIZATION => ['Authorization', 'Validity'],
            self::VENDOR_MASTER => ['Validity', 'Restricted Access'],
            self::SEGREGATION_OF_DUTIES => ['Segregation', 'Authorization'],
            self::APPROVAL_LIMITS => ['Authorization', 'Segregation'],
            self::PERIOD_END_CUTOFF => ['Cutoff', 'Completeness'],
            self::ACCRUAL_RECOGNITION => ['Completeness', 'Valuation'],
            self::INTERCOMPANY_TRANSACTIONS => ['Completeness', 'Accuracy', 'Validity'],
        };
    }

    /**
     * Get testing frequency per year.
     */
    public function getTestingFrequency(): string
    {
        return match ($this) {
            self::THREE_WAY_MATCH,
            self::PAYMENT_AUTHORIZATION,
            self::INVOICE_PROCESSING => 'QUARTERLY',
            self::PURCHASE_ORDER_APPROVAL,
            self::GOODS_RECEIPT,
            self::PERIOD_END_CUTOFF => 'QUARTERLY',
            self::SEGREGATION_OF_DUTIES,
            self::APPROVAL_LIMITS,
            self::VENDOR_MASTER => 'ANNUALLY',
            self::PURCHASE_REQUISITION,
            self::ACCRUAL_RECOGNITION,
            self::INTERCOMPANY_TRANSACTIONS => 'ANNUALLY',
        };
    }

    /**
     * Check if control is key control for SOX.
     */
    public function isKeyControl(): bool
    {
        return match ($this) {
            self::THREE_WAY_MATCH,
            self::PAYMENT_AUTHORIZATION,
            self::SEGREGATION_OF_DUTIES,
            self::APPROVAL_LIMITS,
            self::VENDOR_MASTER,
            self::PERIOD_END_CUTOFF => true,
            default => false,
        };
    }

    /**
     * Get all key controls.
     *
     * @return array<self>
     */
    public static function keyControls(): array
    {
        return array_filter(
            self::cases(),
            fn(self $control) => $control->isKeyControl()
        );
    }
}
