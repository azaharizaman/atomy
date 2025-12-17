<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SOX;

use Nexus\ProcurementOperations\Enums\P2PStep;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;

/**
 * Request to validate SOX controls for a procurement transaction.
 */
final readonly class SOXControlValidationRequest
{
    /**
     * @param array<SOXControlPoint>|null $controlsToValidate Specific controls to validate (null = all applicable)
     * @param array<string, mixed> $context Transaction context data
     * @param array<string, mixed> $metadata Additional request metadata
     */
    public function __construct(
        public string $tenantId,
        public string $transactionId,
        public string $transactionType,
        public P2PStep $p2pStep,
        public string $userId,
        public ?array $controlsToValidate = null,
        public array $context = [],
        public bool $allowOverrides = true,
        public int $timeoutMs = 5000,
        public array $metadata = [],
    ) {}

    /**
     * Get the controls to validate.
     * If not specified, returns all controls for the P2P step.
     *
     * @return array<SOXControlPoint>
     */
    public function getControlsToValidate(): array
    {
        if ($this->controlsToValidate !== null) {
            return $this->controlsToValidate;
        }

        return SOXControlPoint::getControlsForStep($this->p2pStep);
    }

    /**
     * Check if a specific control should be validated.
     */
    public function shouldValidateControl(SOXControlPoint $control): bool
    {
        if ($this->controlsToValidate === null) {
            return $control->getP2PStep() === $this->p2pStep;
        }

        return in_array($control, $this->controlsToValidate, true);
    }

    /**
     * Get context value by key.
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Create a request for requisition controls.
     *
     * @param array<string, mixed> $context
     */
    public static function forRequisition(
        string $tenantId,
        string $requisitionId,
        string $userId,
        array $context = [],
    ): self {
        return new self(
            tenantId: $tenantId,
            transactionId: $requisitionId,
            transactionType: 'requisition',
            p2pStep: P2PStep::REQUISITION,
            userId: $userId,
            context: $context,
        );
    }

    /**
     * Create a request for purchase order controls.
     *
     * @param array<string, mixed> $context
     */
    public static function forPurchaseOrder(
        string $tenantId,
        string $purchaseOrderId,
        string $userId,
        array $context = [],
    ): self {
        return new self(
            tenantId: $tenantId,
            transactionId: $purchaseOrderId,
            transactionType: 'purchase_order',
            p2pStep: P2PStep::PO_CREATION,
            userId: $userId,
            context: $context,
        );
    }

    /**
     * Create a request for goods receipt controls.
     *
     * @param array<string, mixed> $context
     */
    public static function forGoodsReceipt(
        string $tenantId,
        string $goodsReceiptId,
        string $userId,
        array $context = [],
    ): self {
        return new self(
            tenantId: $tenantId,
            transactionId: $goodsReceiptId,
            transactionType: 'goods_receipt',
            p2pStep: P2PStep::GOODS_RECEIPT,
            userId: $userId,
            context: $context,
        );
    }

    /**
     * Create a request for invoice matching controls.
     *
     * @param array<string, mixed> $context
     */
    public static function forInvoiceMatching(
        string $tenantId,
        string $invoiceId,
        string $userId,
        array $context = [],
    ): self {
        return new self(
            tenantId: $tenantId,
            transactionId: $invoiceId,
            transactionType: 'invoice',
            p2pStep: P2PStep::INVOICE_MATCH,
            userId: $userId,
            context: $context,
        );
    }

    /**
     * Create a request for payment controls.
     *
     * @param array<string, mixed> $context
     */
    public static function forPayment(
        string $tenantId,
        string $paymentId,
        string $userId,
        array $context = [],
    ): self {
        return new self(
            tenantId: $tenantId,
            transactionId: $paymentId,
            transactionType: 'payment',
            p2pStep: P2PStep::PAYMENT,
            userId: $userId,
            context: $context,
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'transaction_id' => $this->transactionId,
            'transaction_type' => $this->transactionType,
            'p2p_step' => $this->p2pStep->value,
            'user_id' => $this->userId,
            'controls_to_validate' => $this->controlsToValidate !== null
                ? array_map(fn (SOXControlPoint $c) => $c->value, $this->controlsToValidate)
                : null,
            'context' => $this->context,
            'allow_overrides' => $this->allowOverrides,
            'timeout_ms' => $this->timeoutMs,
            'metadata' => $this->metadata,
        ];
    }
}
