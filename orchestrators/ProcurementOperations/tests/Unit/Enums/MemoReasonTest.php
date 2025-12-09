<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\MemoReason;
use PHPUnit\Framework\TestCase;

final class MemoReasonTest extends TestCase
{
    /**
     * @dataProvider creditMemoReasonProvider
     */
    public function test_credit_memo_reasons_are_identified_correctly(MemoReason $reason): void
    {
        $this->assertTrue($reason->isCreditMemoReason());
    }

    /**
     * @dataProvider debitMemoReasonProvider
     */
    public function test_debit_memo_reasons_are_identified_correctly(MemoReason $reason): void
    {
        $this->assertFalse($reason->isCreditMemoReason());
    }

    /**
     * @dataProvider approvalRequiredReasonProvider
     */
    public function test_reasons_requiring_approval(MemoReason $reason): void
    {
        $this->assertTrue($reason->requiresApproval());
    }

    /**
     * @dataProvider noApprovalRequiredReasonProvider
     */
    public function test_reasons_not_requiring_approval(MemoReason $reason): void
    {
        $this->assertFalse($reason->requiresApproval());
    }

    public function test_all_reasons_have_descriptions(): void
    {
        foreach (MemoReason::cases() as $reason) {
            $this->assertNotEmpty($reason->getDescription());
        }
    }

    public function test_price_correction_is_credit_memo_reason(): void
    {
        $this->assertTrue(MemoReason::PRICE_CORRECTION->isCreditMemoReason());
    }

    public function test_price_increase_is_debit_memo_reason(): void
    {
        $this->assertFalse(MemoReason::PRICE_INCREASE->isCreditMemoReason());
    }

    public function test_goodwill_credit_requires_approval(): void
    {
        $this->assertTrue(MemoReason::GOODWILL_CREDIT->requiresApproval());
    }

    public function test_damaged_goods_does_not_require_approval(): void
    {
        $this->assertFalse(MemoReason::DAMAGED_GOODS->requiresApproval());
    }

    /**
     * @return array<string, array{MemoReason}>
     */
    public static function creditMemoReasonProvider(): array
    {
        return [
            'price_correction' => [MemoReason::PRICE_CORRECTION],
            'quantity_adjustment' => [MemoReason::QUANTITY_ADJUSTMENT],
            'returned_goods' => [MemoReason::RETURNED_GOODS],
            'damaged_goods' => [MemoReason::DAMAGED_GOODS],
            'early_payment_discount' => [MemoReason::EARLY_PAYMENT_DISCOUNT],
            'volume_rebate' => [MemoReason::VOLUME_REBATE],
            'defective_product' => [MemoReason::DEFECTIVE_PRODUCT],
            'goodwill_credit' => [MemoReason::GOODWILL_CREDIT],
        ];
    }

    /**
     * @return array<string, array{MemoReason}>
     */
    public static function debitMemoReasonProvider(): array
    {
        return [
            'price_increase' => [MemoReason::PRICE_INCREASE],
            'additional_charges' => [MemoReason::ADDITIONAL_CHARGES],
            'freight_charges' => [MemoReason::FREIGHT_CHARGES],
            'handling_fees' => [MemoReason::HANDLING_FEES],
            'restocking_fee' => [MemoReason::RESTOCKING_FEE],
            'late_payment_penalty' => [MemoReason::LATE_PAYMENT_PENALTY],
        ];
    }

    /**
     * @return array<string, array{MemoReason}>
     */
    public static function approvalRequiredReasonProvider(): array
    {
        return [
            'goodwill_credit' => [MemoReason::GOODWILL_CREDIT],
            'price_increase' => [MemoReason::PRICE_INCREASE],
            'additional_charges' => [MemoReason::ADDITIONAL_CHARGES],
            'late_payment_penalty' => [MemoReason::LATE_PAYMENT_PENALTY],
        ];
    }

    /**
     * @return array<string, array{MemoReason}>
     */
    public static function noApprovalRequiredReasonProvider(): array
    {
        return [
            'price_correction' => [MemoReason::PRICE_CORRECTION],
            'quantity_adjustment' => [MemoReason::QUANTITY_ADJUSTMENT],
            'returned_goods' => [MemoReason::RETURNED_GOODS],
            'damaged_goods' => [MemoReason::DAMAGED_GOODS],
            'early_payment_discount' => [MemoReason::EARLY_PAYMENT_DISCOUNT],
            'volume_rebate' => [MemoReason::VOLUME_REBATE],
            'defective_product' => [MemoReason::DEFECTIVE_PRODUCT],
            'freight_charges' => [MemoReason::FREIGHT_CHARGES],
            'handling_fees' => [MemoReason::HANDLING_FEES],
            'restocking_fee' => [MemoReason::RESTOCKING_FEE],
        ];
    }
}
