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
 * Check payment strategy.
 *
 * - Low cost (postage + processing)
 * - 5 business day processing
 * - Requires mailing address
 * - Supports domestic and international
 */
final readonly class CheckPaymentStrategy implements PaymentMethodStrategyInterface
{
    private const FEE_CENTS = 150; // $1.50 per check (processing + postage)

    public function __construct(
        private string $currency = 'MYR',
        private ?SecureIdGeneratorInterface $idGenerator = null,
    ) {}

    public function getMethod(): string
    {
        return PaymentMethod::CHECK->value;
    }

    public function getName(): string
    {
        return 'Check Payment';
    }

    public function supports(PaymentRequest $request): bool
    {
        // Check requires mailing address
        return $request->hasMailingAddress();
    }

    /**
     * @return array<string>
     */
    public function validate(PaymentRequest $request): array
    {
        $errors = [];

        if (!$request->hasMailingAddress()) {
            $errors[] = 'Mailing address is required for check payments';
        }

        if ($request->urgent) {
            $errors[] = 'Check payments do not support urgent delivery';
        }

        return $errors;
    }

    public function execute(PaymentRequest $request): PaymentExecutionResult
    {
        $errors = $this->validate($request);

        if (count($errors) > 0) {
            return PaymentExecutionResult::failure(
                message: 'Check payment validation failed',
                errors: $errors,
            );
        }

        // In real implementation, this would integrate with check printing/mailing service
        $paymentId = $this->idGenerator !== null
            ? $this->idGenerator->generateId('CHK-', 8)
            : 'CHK-' . bin2hex(random_bytes(8));
        $checkNumber = 'CHK' . date('Ymd') . random_int(100000, 999999);

        return PaymentExecutionResult::success(
            paymentId: $paymentId,
            transactionReference: $checkNumber,
            methodUsed: PaymentMethod::CHECK,
            amountPaid: $request->amount,
            feeAmount: $this->calculateFee($request->amount),
            expectedClearingDate: $this->calculateClearingDate(),
            message: 'Check payment queued for printing and mailing',
            metadata: [
                'mailing_address_id' => $request->mailingAddressId,
                'check_number' => $checkNumber,
                'processing_days' => $this->getProcessingDays(),
                'estimated_delivery' => $this->calculateClearingDate()->format('Y-m-d'),
            ],
        );
    }

    public function getProcessingDays(): int
    {
        return 5;
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
        return true;
    }

    public function getSelectionPriority(): int
    {
        return 4; // Fallback method
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
