<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Strategies\Payment;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\PaymentMethodStrategyInterface;
use Nexus\ProcurementOperations\DTOs\PaymentExecutionResult;
use Nexus\ProcurementOperations\DTOs\PaymentRequest;
use Nexus\ProcurementOperations\Enums\PaymentMethod;

/**
 * Virtual card payment strategy.
 *
 * - May earn rebates (1-2%)
 * - Instant processing
 * - No bank account or address required
 * - Vendor must accept card payments
 */
final readonly class VirtualCardPaymentStrategy implements PaymentMethodStrategyInterface
{
    private const FEE_PERCENTAGE = 0.0; // No fee (may even earn rebates)

    public function __construct(
        private string $currency = 'MYR',
    ) {}

    public function getMethod(): string
    {
        return PaymentMethod::VIRTUAL_CARD->value;
    }

    public function getName(): string
    {
        return 'Virtual Card Payment';
    }

    public function supports(PaymentRequest $request): bool
    {
        // Virtual card is available for most payments
        // In reality, would check if vendor accepts card payments
        return true;
    }

    /**
     * @return array<string>
     */
    public function validate(PaymentRequest $request): array
    {
        $errors = [];

        // Virtual cards may have transaction limits
        if ($request->amount->getAmountInCents() > 10000000) { // $100,000 limit
            $errors[] = 'Virtual card payments have a maximum limit of $100,000';
        }

        return $errors;
    }

    public function execute(PaymentRequest $request): PaymentExecutionResult
    {
        $errors = $this->validate($request);

        if (count($errors) > 0) {
            return PaymentExecutionResult::failure(
                message: 'Virtual card payment validation failed',
                errors: $errors,
            );
        }

        // In real implementation, this would generate virtual card number
        $paymentId = 'VCARD-' . bin2hex(random_bytes(8));
        $cardRef = 'VC' . date('Ymd') . random_int(100000, 999999);
        $virtualCardNumber = $this->generateVirtualCardNumber();

        return PaymentExecutionResult::success(
            paymentId: $paymentId,
            transactionReference: $cardRef,
            methodUsed: PaymentMethod::VIRTUAL_CARD,
            amountPaid: $request->amount,
            feeAmount: $this->calculateFee($request->amount),
            expectedClearingDate: new \DateTimeImmutable(),
            message: 'Virtual card generated and sent to vendor',
            metadata: [
                'virtual_card_last_four' => substr($virtualCardNumber, -4),
                'card_expiry' => (new \DateTimeImmutable('+30 days'))->format('m/y'),
                'single_use' => true,
            ],
        );
    }

    public function getProcessingDays(): int
    {
        return 0;
    }

    public function calculateFee(Money $amount): Money
    {
        // Virtual cards typically have no fee and may earn rebates
        return Money::of(0, $this->currency);
    }

    /**
     * Calculate potential rebate earned.
     */
    public function calculateRebate(Money $amount): Money
    {
        // Typical rebate is 1-2% of transaction
        $rebateCents = (int) floor($amount->getAmountInCents() * 0.015);
        return Money::of($rebateCents, $this->currency);
    }

    public function supportsSameDay(): bool
    {
        return true;
    }

    public function supportsInternational(): bool
    {
        return true;
    }

    public function getSelectionPriority(): int
    {
        return 2; // Second preferred due to potential rebates
    }

    private function generateVirtualCardNumber(): string
    {
        // In real implementation, this would call the card provider API
        return '4' . str_pad((string) random_int(0, 999999999999999), 15, '0', STR_PAD_LEFT);
    }
}
