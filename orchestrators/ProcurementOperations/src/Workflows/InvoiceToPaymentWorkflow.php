<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows;

use Nexus\ProcurementOperations\Contracts\SagaInterface;
use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\Contracts\WorkflowStorageInterface;
use Nexus\ProcurementOperations\DTOs\SagaContext;
use Nexus\ProcurementOperations\DTOs\SagaResult;
use Nexus\ProcurementOperations\Events\InvoiceToPaymentStartedEvent;
use Nexus\ProcurementOperations\Events\InvoiceToPaymentCompletedEvent;
use Nexus\ProcurementOperations\Events\InvoiceToPaymentFailedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Invoice-to-Payment Workflow.
 *
 * Orchestrates the invoice processing and payment execution workflow:
 * 1. Receive and validate vendor invoice
 * 2. Match invoice to PO and goods receipt (3-way match)
 * 3. Route for approval if required
 * 4. Schedule or execute payment
 * 5. Update vendor ledger and post GL entries
 * 6. Notify vendor of payment
 *
 * Supports:
 * - Auto-approval based on matching rules
 * - Variance handling (price, quantity)
 * - Partial payments
 * - Payment batching
 * - Early payment discount optimization
 */
final class InvoiceToPaymentWorkflow extends AbstractSaga implements SagaInterface
{
    private const WORKFLOW_ID = 'invoice_to_payment_workflow';

    /**
     * @param array<SagaStepInterface> $steps
     */
    public function __construct(
        private readonly array $steps,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($storage, $eventDispatcher, $logger ?? new NullLogger());
    }

    /**
     * Create workflow with default steps.
     *
     * @param array<SagaStepInterface> $steps
     */
    public static function create(
        array $steps,
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ): self {
        return new self(
            steps: $steps,
            storage: $storage,
            eventDispatcher: $eventDispatcher,
            logger: $logger,
        );
    }

    public function getId(): string
    {
        return self::WORKFLOW_ID;
    }

    public function getName(): string
    {
        return 'Invoice-to-Payment Workflow';
    }

    public function getDescription(): string
    {
        return 'End-to-end invoice processing workflow from receipt to payment execution';
    }

    /**
     * @return array<SagaStepInterface>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Execute the invoice-to-payment workflow.
     */
    public function execute(SagaContext $context): SagaResult
    {
        $invoiceId = $context->data['invoiceId'] ?? null;
        $vendorId = $context->data['vendorId'] ?? null;
        $amountCents = $context->data['totalAmountCents'] ?? 0;

        // Dispatch workflow started event
        $this->eventDispatcher->dispatch(new InvoiceToPaymentStartedEvent(
            tenantId: $context->tenantId,
            invoiceId: $invoiceId,
            vendorId: $vendorId,
            amountCents: $amountCents,
            initiatedBy: $context->userId,
            startedAt: new \DateTimeImmutable(),
        ));

        // Execute base saga logic
        $result = parent::execute($context);

        // Dispatch completion/failure event based on result
        if ($result->success) {
            $this->eventDispatcher->dispatch(new InvoiceToPaymentCompletedEvent(
                tenantId: $context->tenantId,
                invoiceId: $invoiceId,
                paymentId: $result->data['paymentId'] ?? null,
                paidAmountCents: $result->data['paidAmountCents'] ?? $amountCents,
                completedAt: new \DateTimeImmutable(),
            ));
        } else {
            $this->eventDispatcher->dispatch(new InvoiceToPaymentFailedEvent(
                tenantId: $context->tenantId,
                invoiceId: $invoiceId,
                failureReason: $result->errorMessage,
                failedStep: $result->data['failedStep'] ?? null,
                failedAt: new \DateTimeImmutable(),
            ));
        }

        return $result;
    }

    /**
     * Process a vendor invoice through the workflow.
     *
     * @param array<string, mixed> $invoiceData
     */
    public function processInvoice(
        string $tenantId,
        string $invoiceId,
        string $vendorId,
        array $invoiceData,
        string $processedBy,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $processedBy,
            data: array_merge($invoiceData, [
                'invoiceId' => $invoiceId,
                'vendorId' => $vendorId,
            ]),
            metadata: ['receivedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)],
        );

        return $this->execute($context);
    }

    /**
     * Process invoice with auto-matching to PO.
     *
     * @param array<string, mixed> $invoiceData
     */
    public function processWithAutoMatch(
        string $tenantId,
        string $invoiceId,
        string $vendorId,
        string $purchaseOrderId,
        array $invoiceData,
        string $processedBy,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $processedBy,
            data: array_merge($invoiceData, [
                'invoiceId' => $invoiceId,
                'vendorId' => $vendorId,
                'purchaseOrderId' => $purchaseOrderId,
                'autoMatch' => true,
            ]),
            metadata: ['receivedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)],
        );

        return $this->execute($context);
    }

    /**
     * Apply approval decision for an invoice.
     */
    public function applyApprovalDecision(
        string $tenantId,
        string $invoiceId,
        string $approverId,
        string $decision,
        ?string $comments = null,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $approverId,
            data: [
                'invoiceId' => $invoiceId,
                'action' => 'approval_decision',
                'decision' => $decision,
                'comments' => $comments,
            ],
            metadata: ['decidedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)],
        );

        return $this->resume($invoiceId, $context->data);
    }

    /**
     * Handle variance resolution for an invoice.
     */
    public function resolveVariance(
        string $tenantId,
        string $invoiceId,
        string $varianceType,
        string $resolution,
        ?int $adjustedAmountCents = null,
        ?string $resolvedBy = null,
        ?string $reason = null,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $resolvedBy ?? 'system',
            data: [
                'invoiceId' => $invoiceId,
                'action' => 'resolve_variance',
                'varianceType' => $varianceType,
                'resolution' => $resolution,
                'adjustedAmountCents' => $adjustedAmountCents,
                'reason' => $reason,
            ],
            metadata: ['resolvedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)],
        );

        return $this->resume($invoiceId, $context->data);
    }

    /**
     * Schedule payment for approved invoice.
     */
    public function schedulePayment(
        string $tenantId,
        string $invoiceId,
        \DateTimeImmutable $paymentDate,
        string $paymentMethod,
        string $bankAccountId,
        string $scheduledBy,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $scheduledBy,
            data: [
                'invoiceId' => $invoiceId,
                'action' => 'schedule_payment',
                'paymentDate' => $paymentDate->format(\DateTimeInterface::ATOM),
                'paymentMethod' => $paymentMethod,
                'bankAccountId' => $bankAccountId,
            ],
            metadata: ['scheduledAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)],
        );

        return $this->resume($invoiceId, $context->data);
    }

    /**
     * Execute immediate payment for invoice.
     */
    public function executePayment(
        string $tenantId,
        string $invoiceId,
        string $paymentMethod,
        string $bankAccountId,
        string $executedBy,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $executedBy,
            data: [
                'invoiceId' => $invoiceId,
                'action' => 'execute_payment',
                'paymentMethod' => $paymentMethod,
                'bankAccountId' => $bankAccountId,
                'immediate' => true,
            ],
            metadata: ['executedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)],
        );

        return $this->resume($invoiceId, $context->data);
    }

    /**
     * Cancel invoice processing.
     */
    public function cancelProcessing(
        string $tenantId,
        string $invoiceId,
        string $cancelledBy,
        string $reason,
    ): SagaResult {
        $context = new SagaContext(
            tenantId: $tenantId,
            userId: $cancelledBy,
            data: [
                'invoiceId' => $invoiceId,
                'action' => 'cancel',
                'reason' => $reason,
            ],
            metadata: ['cancelledAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)],
        );

        return $this->cancel($invoiceId, $context->data);
    }

    /**
     * Get workflow status for an invoice.
     *
     * @return array{
     *     status: string,
     *     currentStep: ?string,
     *     invoiceStatus: string,
     *     matchingStatus: ?string,
     *     approvalStatus: ?string,
     *     paymentStatus: ?string,
     *     variances: array<array{type: string, amount: int, status: string}>
     * }
     */
    public function getWorkflowStatus(string $tenantId, string $invoiceId): array
    {
        $state = $this->getState($invoiceId);

        if ($state === null) {
            return [
                'status' => 'not_found',
                'currentStep' => null,
                'invoiceStatus' => 'unknown',
                'matchingStatus' => null,
                'approvalStatus' => null,
                'paymentStatus' => null,
                'variances' => [],
            ];
        }

        $data = $state->getData();

        return [
            'status' => $state->getStatus()->value,
            'currentStep' => $state->getCurrentStepId(),
            'invoiceStatus' => $data['invoiceStatus'] ?? 'processing',
            'matchingStatus' => $data['matchingStatus'] ?? null,
            'approvalStatus' => $data['approvalStatus'] ?? null,
            'paymentStatus' => $data['paymentStatus'] ?? null,
            'variances' => $data['variances'] ?? [],
        ];
    }

    /**
     * Check if early payment discount is available.
     *
     * @return array{available: bool, discountPercent: ?float, discountAmount: ?int, deadline: ?\DateTimeImmutable}
     */
    public function checkEarlyPaymentDiscount(string $tenantId, string $invoiceId): array
    {
        $state = $this->getState($invoiceId);

        if ($state === null) {
            return [
                'available' => false,
                'discountPercent' => null,
                'discountAmount' => null,
                'deadline' => null,
            ];
        }

        $data = $state->getData();

        // Check if discount terms exist and deadline hasn't passed
        $discountDeadline = $data['earlyPaymentDiscountDeadline'] ?? null;
        $discountPercent = $data['earlyPaymentDiscountPercent'] ?? null;
        $invoiceAmount = $data['totalAmountCents'] ?? 0;

        if ($discountDeadline === null || $discountPercent === null) {
            return [
                'available' => false,
                'discountPercent' => null,
                'discountAmount' => null,
                'deadline' => null,
            ];
        }

        $deadline = new \DateTimeImmutable($discountDeadline);
        $now = new \DateTimeImmutable();

        return [
            'available' => $deadline > $now,
            'discountPercent' => $discountPercent,
            'discountAmount' => (int) ($invoiceAmount * $discountPercent / 100),
            'deadline' => $deadline,
        ];
    }
}
