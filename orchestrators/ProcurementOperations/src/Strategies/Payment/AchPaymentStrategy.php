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
 * ACH (Automated Clearing House) payment strategy.
 *
 * - Low cost ($0.20-$1.50 per transaction)
 * - 2 business day processing
 * - Requires bank account details
 * - Domestic only (US)
 */
final readonly class AchPaymentStrategy implements PaymentMethodStrategyInterface
{
    private const FEE_CENTS = 50; // $0.50 flat fee

    public function __construct(
        private string $currency = 'MYR',
        private ?SecureIdGeneratorInterface $idGenerator = null,
    ) {}

    public function getMethod(): string
    {
        return PaymentMethod::ACH->value;
    }

    public function getName(): string
    {
        return 'ACH Transfer';
    }

    public function supports(PaymentRequest $request): bool
    {
        // ACH requires bank account and doesn't support international
        return $request->hasBankAccount() && !$request->international;
    }

    /**
     * @return array<string>
     */
    public function validate(PaymentRequest $request): array
    {
        $errors = [];

        if (!$request->hasBankAccount()) {
            $errors[] = 'Bank account is required for ACH payments';
        }

        if ($request->international) {
            $errors[] = 'ACH does not support international payments';
        }

        if ($request->urgent) {
            $errors[] = 'ACH does not support same-day urgent payments';
        }

        return $errors;
    }

    public function execute(PaymentRequest $request): PaymentExecutionResult
    {
        $errors = $this->validate($request);

        if (count($errors) > 0) {
            return PaymentExecutionResult::failure(
                message: 'ACH payment validation failed',
                errors: $errors,
            );
        }

        // In real implementation, this would integrate with ACH processor
        $paymentId = $this->idGenerator !== null
            ? $this->idGenerator->generateId('ACH-', 8)
            : 'ACH-' . bin2hex(random_bytes(8));
        $transactionRef = 'ACH' . date('Ymd') . random_int(100000, 999999);

        return PaymentExecutionResult::success(
            paymentId: $paymentId,
            transactionReference: $transactionRef,
            methodUsed: PaymentMethod::ACH,
            amountPaid: $request->amount,
            feeAmount: $this->calculateFee($request->amount),
            expectedClearingDate: $this->calculateClearingDate(),
            message: 'ACH payment initiated successfully',
            metadata: [
                'bank_account_id' => $request->bankAccountId,
                'processing_days' => $this->getProcessingDays(),
            ],
        );
    }

    public function getProcessingDays(): int
    {
        return 2;
    }

    public function calculateFee(Money $amount): Money
    {
        return Money::of(self::FEE_CENTS, $this->currency);
    }

    public function supportsSameDay(): bool
    {
        return false;
    }

    public function supportsInternational(): bool
    {
        return false;
    }

    public function getSelectionPriority(): int
    {
        return 1; // Most preferred due to low cost
    }

    private function calculateClearingDate(): \DateTimeImmutable
    {
        $date = new \DateTimeImmutable();
        $daysAdded = 0;

        while ($daysAdded < $this->getProcessingDays()) {
            $date = $date->modify('+1 day');
            // Skip weekends
            if ((int) $date->format('N') < 6) {
                $daysAdded++;
            }
        }

        return $date;
    }
}
