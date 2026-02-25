<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use DateTimeImmutable;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Currency\Contracts\ExchangeRateServiceInterface;
use Nexus\Sales\Contracts\CreditLimitCheckerInterface;
use Nexus\Sales\Contracts\InvoiceManagerInterface;
use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Contracts\SalesSettingInterface;
use Nexus\Sales\Contracts\StockReservationInterface;
use Nexus\Sales\Enums\PaymentTerm;
use Nexus\Sales\Enums\SalesOrderStatus;
use Nexus\Sales\Exceptions\ExchangeRateLockedException;
use Nexus\Sales\Exceptions\InvalidOrderStatusException;
use Nexus\Sales\Exceptions\SalesOrderNotFoundException;
use Nexus\Sales\ValueObjects\SalesOrderData;
use Nexus\Sales\Services\Traits\ResolvesPaymentTerm;
use Nexus\Sequencing\Contracts\SequenceGeneratorInterface;
use Psr\Log\LoggerInterface;

/**
 * Sales order lifecycle management service.
 */
final readonly class SalesOrderManager
{
    use ResolvesPaymentTerm;

    public function __construct(
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private SequenceGeneratorInterface $sequenceGenerator,
        private ExchangeRateServiceInterface $exchangeRateService,
        private SalesSettingInterface $salesSetting,
        private CreditLimitCheckerInterface $creditLimitChecker,
        private StockReservationInterface $stockReservation,
        private InvoiceManagerInterface $invoiceManager,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    /**
     * Create a new sales order (draft status).
     *
     * @param string $tenantId
     * @param string $customerId
     * @param array $lines Array of line data
     * @param array $data Additional order data:
     *                     - currency_code: string (default: 'MYR')
     *                     - payment_term: PaymentTerm enum
     *                     - shipping_address: string|null
     *                     - billing_address: string|null
     *                     - customer_po: string|null
     *                     - notes: string|null
     *                     - salesperson_id: string|null
     *                     - preferred_warehouse_id: string|null
     *                     - discount_rule: DiscountRule|null
     * @return SalesOrderInterface
     */
    public function createOrder(
        string $tenantId,
        string $customerId,
        array $lines,
        array $data
    ): SalesOrderInterface {
        // Generate order number
        $orderNumber = $this->sequenceGenerator->generate(
            $tenantId,
            'sales_order',
            ['prefix' => 'SO']
        );

        $orderDate = new DateTimeImmutable();
        $currencyCode = $data['currency_code'] ?? 'MYR';
        $paymentTerm = $this->resolvePaymentTerm($data['payment_term'] ?? null);

        // Calculate totals from lines
        $subtotal = 0.0;
        $taxAmount = 0.0;
        $discountAmount = 0.0;
        $lineSequence = 1;
        $orderLines = [];

        foreach ($lines as $line) {
            $lineSubtotal = ($line['quantity'] ?? 0) * ($line['unit_price'] ?? 0);
            $lineTaxAmount = $line['tax_amount'] ?? 0.0;
            $lineDiscount = $line['discount_amount'] ?? 0.0;
            $lineTotal = $lineSubtotal + $lineTaxAmount - $lineDiscount;

            $subtotal += $lineSubtotal;
            $taxAmount += $lineTaxAmount;
            $discountAmount += $lineDiscount;

            $orderLines[] = [
                'product_variant_id' => $line['product_variant_id'],
                'quantity' => $line['quantity'],
                'uom_code' => $line['uom_code'] ?? 'EA',
                'unit_price' => $line['unit_price'],
                'line_subtotal' => $lineSubtotal,
                'tax_amount' => $lineTaxAmount,
                'discount_amount' => $lineDiscount,
                'line_total' => $lineTotal,
                'discount_rule' => $line['discount_rule'] ?? null,
                'line_notes' => $line['line_notes'] ?? null,
                'line_sequence' => $lineSequence++,
            ];
        }

        $total = $subtotal + $taxAmount - $discountAmount;

        // Build order data object
        $orderData = new SalesOrderData(
            tenantId: $tenantId,
            customerId: $customerId,
            currencyCode: $currencyCode,
            quoteDate: $orderDate->format('Y-m-d'),
            lines: $orderLines,
            orderNumber: $orderNumber,
            warehouseId: $data['preferred_warehouse_id'] ?? null,
            salespersonId: $data['salesperson_id'] ?? null,
            shippingAddressId: $data['shipping_address_id'] ?? null,
            billingAddressId: $data['billing_address_id'] ?? null,
            paymentTerm: $data['payment_term'] ?? null,
            customerPoNumber: $data['customer_po'] ?? null,
            customerNotes: $data['notes'] ?? null,
            internalNotes: $data['internal_notes'] ?? null,
            exchangeRate: null,
            metadata: [
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'payment_due_date' => $paymentTerm->calculateDueDate($orderDate)->format('Y-m-d'),
            ],
        );

        // Create and save the sales order via repository
        $salesOrder = $this->salesOrderRepository->create($orderData);

        $this->logger->info('Sales order created', [
            'tenant_id' => $tenantId,
            'order_id' => $salesOrder->getId(),
            'order_number' => $orderNumber,
            'customer_id' => $customerId,
            'total' => $total,
            'currency' => $currencyCode,
        ]);

        $this->auditLogger->log(
            $tenantId,
            'order_created',
            "Sales order {$orderNumber} created",
            [
                'order_id' => $salesOrder->getId(),
                'order_number' => $orderNumber,
                'customer_id' => $customerId,
                'total' => $total,
                'currency' => $currencyCode,
            ]
        );

        return $salesOrder;
    }

    /**
     * Confirm sales order (lock exchange rate, check credit, reserve stock).
     *
     * @param string $orderId
     * @param string $confirmedBy User ID who confirmed the order
     * @return void
     * @throws SalesOrderNotFoundException
     * @throws InvalidOrderStatusException
     * @throws \Nexus\Sales\Exceptions\CreditLimitExceededException
     * @throws \Nexus\Sales\Exceptions\InsufficientStockException
     */
    public function confirmOrder(string $orderId, string $confirmedBy): void
    {
        $order = $this->salesOrderRepository->findById($orderId);

        if (!$order->getStatus()->canBeConfirmed()) {
            throw InvalidOrderStatusException::cannotConfirm($orderId, $order->getStatus());
        }

        // 1. Check credit limit
        $this->creditLimitChecker->checkCreditLimit(
            $order->getTenantId(),
            $order->getCustomerId(),
            $order->getTotal(),
            $order->getCurrencyCode()
        );

        // 2. Lock exchange rate (if foreign currency)
        if ($order->getExchangeRate() === null) {
            $baseCurrency = $this->salesSetting->getBaseCurrency($order->getTenantId());
            if ($order->getCurrencyCode() !== $baseCurrency) {
                $exchangeRate = $this->exchangeRateService->getRate(
                    $order->getCurrencyCode(),
                    $baseCurrency,
                    new DateTimeImmutable()
                );
                // Set exchange rate on order (implementation-specific mutation)
            }
        }

        // 3. Reserve stock
        $this->stockReservation->reserveStockForOrder($orderId);

        // 4. Update status to CONFIRMED
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $orderId,
            'order_confirmed',
            "Sales order {$order->getOrderNumber()} confirmed by {$confirmedBy}"
        );

        $this->logger->info('Sales order confirmed', [
            'order_id' => $orderId,
            'order_number' => $order->getOrderNumber(),
            'confirmed_by' => $confirmedBy,
        ]);
    }

    /**
     * Cancel sales order (release stock reservation).
     *
     * @param string $orderId
     * @param string|null $reason
     * @return void
     * @throws SalesOrderNotFoundException
     * @throws InvalidOrderStatusException
     */
    public function cancelOrder(string $orderId, ?string $reason = null): void
    {
        $order = $this->salesOrderRepository->findById($orderId);

        if ($order->getStatus()->isFinal()) {
            throw InvalidOrderStatusException::cannotTransition(
                $orderId,
                $order->getStatus(),
                SalesOrderStatus::CANCELLED
            );
        }

        // Release stock reservation
        $this->stockReservation->releaseStockReservation($orderId);

        // Update status to CANCELLED
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $orderId,
            'order_cancelled',
            "Sales order {$order->getOrderNumber()} cancelled" . ($reason ? ": {$reason}" : '')
        );

        $this->logger->info('Sales order cancelled', [
            'order_id' => $orderId,
            'order_number' => $order->getOrderNumber(),
            'reason' => $reason,
        ]);
    }

    /**
     * Mark order as shipped.
     *
     * @param string $orderId
     * @param bool $isPartialShipment
     * @return void
     * @throws SalesOrderNotFoundException
     * @throws InvalidOrderStatusException
     */
    public function markAsShipped(string $orderId, bool $isPartialShipment = false): void
    {
        $order = $this->salesOrderRepository->findById($orderId);

        if (!$order->getStatus()->canBeShipped()) {
            throw InvalidOrderStatusException::cannotTransition(
                $orderId,
                $order->getStatus(),
                $isPartialShipment ? SalesOrderStatus::PARTIALLY_SHIPPED : SalesOrderStatus::FULLY_SHIPPED
            );
        }

        $newStatus = $isPartialShipment ? SalesOrderStatus::PARTIALLY_SHIPPED : SalesOrderStatus::FULLY_SHIPPED;
        
        // Update status
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $orderId,
            'order_shipped',
            "Sales order {$order->getOrderNumber()} " . ($isPartialShipment ? 'partially' : 'fully') . ' shipped'
        );

        $this->logger->info('Sales order shipped', [
            'order_id' => $orderId,
            'order_number' => $order->getOrderNumber(),
            'is_partial' => $isPartialShipment,
        ]);
    }

    /**
     * Generate invoice from sales order.
     *
     * @param string $orderId
     * @return string Invoice ID
     * @throws SalesOrderNotFoundException
     * @throws InvalidOrderStatusException
     * @throws \BadMethodCallException If Receivable package not installed
     */
    public function generateInvoice(string $orderId): string
    {
        $order = $this->salesOrderRepository->findById($orderId);

        if (!$order->getStatus()->canBeInvoiced()) {
            throw InvalidOrderStatusException::cannotTransition(
                $orderId,
                $order->getStatus(),
                SalesOrderStatus::INVOICED
            );
        }

        // Generate invoice via stub interface (will throw NotImplementedException in V1)
        $invoiceId = $this->invoiceManager->generateInvoiceFromOrder($orderId);

        // Update status to INVOICED
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $orderId,
            'order_invoiced',
            "Invoice generated from sales order {$order->getOrderNumber()}"
        );

        $this->logger->info('Invoice generated from order', [
            'order_id' => $orderId,
            'order_number' => $order->getOrderNumber(),
            'invoice_id' => $invoiceId,
        ]);

        return $invoiceId;
    }

    /**
     * Find sales order by ID.
     *
     * @param string $orderId
     * @return SalesOrderInterface
     * @throws SalesOrderNotFoundException
     */
    public function findOrder(string $orderId): SalesOrderInterface
    {
        return $this->salesOrderRepository->findById($orderId);
    }

    /**
     * Find orders by customer.
     *
     * @param string $tenantId
     * @param string $customerId
     * @return SalesOrderInterface[]
     */
    public function findOrdersByCustomer(string $tenantId, string $customerId): array
    {
        return $this->salesOrderRepository->findByCustomer($tenantId, $customerId);
    }

    /**
     * Resolve payment term from various input formats.
     *
     * @param mixed $paymentTerm Payment term value (PaymentTerm enum, string, or null)
     * @return PaymentTerm
     */
    private function resolvePaymentTerm(mixed $paymentTerm): PaymentTerm
    {
        if ($paymentTerm instanceof PaymentTerm) {
            return $paymentTerm;
        }

        if (is_string($paymentTerm)) {
            return PaymentTerm::from($paymentTerm);
        }

        return PaymentTerm::NET_30;
    }
}
