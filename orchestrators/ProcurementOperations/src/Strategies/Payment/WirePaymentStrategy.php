<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Strategies\Payment;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\PaymentMethodStrategyInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DTOs\PaymentExecutionResult;
use Nexus\ProcurementOperations\DTOs\PaymentRequest;
use Nexus\ProcurementOperations\Enums\PaymentMethod;

/**
 * Wire transfer payment strategy.
 *
 * - Higher cost ($15-$50 per transaction)
 * - 1 business day processing (same-day available)
 * - Requires bank account details
 * - Supports international payments
 */
final readonly class WirePaymentStrategy implements PaymentMethodStrategyInterface
{
    private const BASE_FEE_CENTS = 2500; // $25.00 base fee
    private const INTERNATIONAL_FEE_CENTS = 5000; // $50.00 for international

    public function __construct(
        private string $currency = 'MYR',
        private ?SecureIdGeneratorInterface $idGenerator = null,
    ) {}

    public function getMethod(): string
    {
        return PaymentMethod::WIRE->value;
    }

    public function getName(): string
    {
        return 'Wire Transfer';
    }

    public function supports(PaymentRequest $request): bool
    {
        // Wire requires bank account
        return $request->hasBankAccount();
    }

    /**
     * @return array<string>
     */
    public function validate(PaymentRequest $request): array
    {
        $errors = [];

        if (!$request->hasBankAccount()) {
            $errors[] = 'Bank account is required for wire transfers';
        }

        // Wire typically has minimum amounts
        if ($request->amount->getAmountInCents() < 10000) { // $100 minimum
            $errors[] = 'Wire transfers have a minimum amount of $100';
        }

        return $errors;
    }

    public function execute(PaymentRequest $request): PaymentExecutionResult
    {
        $errors = $this->validate($request);

        if (count($errors) > 0) {
            return PaymentExecutionResult::failure(
                message: 'Wire transfer validation failed',
                errors: $errors,
            );
        }

        // In real implementation, this would integrate with wire transfer processor
        $paymentId = $this->idGenerator !== null
            ? $this->idGenerator->generateId('WIRE-', 8)
            : 'WIRE-' . bin2hex(random_bytes(8));
        $transactionRef = 'WIRE' . date('Ymd') . random_int(100000, 999999);

        return PaymentExecutionResult::success(
            paymentId: $paymentId,
            transactionReference: $transactionRef,
            methodUsed: PaymentMethod::WIRE,
            amountPaid: $request->amount,
            feeAmount: $this->calculateFee($request->amount),
            expectedClearingDate: $this->calculateClearingDate($request->urgent),
            message: 'Wire transfer initiated successfully',
            metadata: [
                'bank_account_id' => $request->bankAccountId,
                'processing_days' => $request->urgent ? 0 : $this->getProcessingDays(),
                'international' => $request->international,
            ],
        );
    }

    public function getProcessingDays(): int
    {
        return 1;
    }

    public function calculateFee(Money $amount): Money
    {
        // Could add international fee logic here
        return Money::of(self::BASE_FEE_CENTS, $this->currency);
    }

    /**
     * Calculate fee including international surcharge if applicable.
     */
    public function calculateFeeWithInternational(Money $amount, bool $international): Money
    {
        $baseFee = $international ? self::INTERNATIONAL_FEE_CENTS : self::BASE_FEE_CENTS;
        return Money::of($baseFee, $this->currency);
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
        return 3; // Used for urgent or international
    }

    private function calculateClearingDate(bool $urgent): \DateTimeImmutable
    {
        if ($urgent) {
            return new \DateTimeImmutable();
        }

        $date = new \DateTimeImmutable();
        $date = $date->modify('+1 day');

        // Skip weekends
        while ((int) $date->format('N') >= 6) {
            $date = $date->modify('+1 day');
        }

        return $date;
    }
}
