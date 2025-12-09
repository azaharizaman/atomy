<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\PaymentExecutionResult;
use Nexus\ProcurementOperations\DTOs\PaymentRequest;

/**
 * Strategy interface for payment method execution.
 *
 * Each payment method (ACH, Wire, Check, etc.) implements this interface
 * to provide method-specific validation and execution logic.
 */
interface PaymentMethodStrategyInterface
{
    /**
     * Get the payment method identifier.
     */
    public function getMethod(): string;

    /**
     * Get human-readable method name.
     */
    public function getName(): string;

    /**
     * Check if this strategy can handle the given payment request.
     */
    public function supports(PaymentRequest $request): bool;

    /**
     * Validate that all required information is available for this payment method.
     *
     * @param PaymentRequest $request Payment request to validate
     * @return array<string> List of validation errors (empty if valid)
     */
    public function validate(PaymentRequest $request): array;

    /**
     * Execute the payment using this method.
     */
    public function execute(PaymentRequest $request): PaymentExecutionResult;

    /**
     * Get estimated processing time in business days.
     */
    public function getProcessingDays(): int;

    /**
     * Calculate the fee for a given amount.
     */
    public function calculateFee(Money $amount): Money;

    /**
     * Check if method supports same-day processing.
     */
    public function supportsSameDay(): bool;

    /**
     * Check if method supports international payments.
     */
    public function supportsInternational(): bool;

    /**
     * Get the priority for auto-selection (lower = preferred).
     */
    public function getSelectionPriority(): int;
}
