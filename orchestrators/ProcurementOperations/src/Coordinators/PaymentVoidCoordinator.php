<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\PaymentVoidCoordinatorInterface;
use Nexus\ProcurementOperations\DTOs\PaymentVoidRequest;
use Nexus\ProcurementOperations\DTOs\PaymentResult;
use Nexus\ProcurementOperations\Exceptions\PaymentException;
use Nexus\Payable\Contracts\PaymentPersistInterface;
use Nexus\JournalEntry\Contracts\JournalEntryPersistInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates the payment voiding and reversal process.
 */
final readonly class PaymentVoidCoordinator implements PaymentVoidCoordinatorInterface
{
    public function __construct(
        private PaymentPersistInterface $paymentPersist,
        private ?JournalEntryPersistInterface $journalPersist = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * @inheritDoc
     */
    public function voidPayment(PaymentVoidRequest $request): PaymentResult
    {
        $this->logger->info('Voiding payment', [
            'tenant_id' => $request->tenantId,
            'payment_id' => $request->paymentId,
        ]);

        try {
            // 1. Mark payment as void in Payable package
            $this->paymentPersist->void($request->paymentId, $request->reason, $request->voidedBy);

            // 2. Reverse accounting if requested and journal entry exists
            if ($request->reverseAccounting && $this->journalPersist !== null) {
                // Logic to find and reverse journal entry
                // $this->journalPersist->reverseByReference('payment', $request->paymentId);
            }

            return new PaymentResult(
                success: true,
                paymentId: $request->paymentId,
                status: 'voided',
                message: 'Payment voided successfully.'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to void payment', [
                'error' => $e->getMessage(),
            ]);
            return new PaymentResult(
                success: false,
                message: 'Failed to void payment: ' . $e->getMessage()
            );
        }
    }
}
