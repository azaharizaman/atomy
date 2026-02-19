<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use DateTimeImmutable;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Sales\Contracts\QuotationInterface;
use Nexus\Sales\Contracts\QuotationRepositoryInterface;
use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Contracts\TransactionManagerInterface;
use Nexus\Sales\Enums\PaymentTerm;
use Nexus\Sales\Enums\QuoteStatus;
use Nexus\Sales\Enums\SalesOrderStatus;
use Nexus\Sales\Exceptions\InvalidQuoteStatusException;
use Nexus\Sales\Exceptions\QuotationNotFoundException;
use Nexus\Sales\ValueObjects\SalesOrderData;
use Nexus\Sales\Services\Traits\ResolvesPaymentTerm;
use Nexus\Sequencing\Contracts\SequenceGeneratorInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for converting quotations to sales orders.
 *
 * This service handles the conversion of an accepted quotation into a sales order,
 * copying all relevant data including line items, pricing, and customer information.
 */
final readonly class QuoteToOrderConverter
{
    use ResolvesPaymentTerm;

    public function __construct(
        private QuotationRepositoryInterface $quotationRepository,
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private SequenceGeneratorInterface $sequenceGenerator,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger,
        private TransactionManagerInterface $transactionManager
    ) {}

    /**
     * Convert accepted quotation to sales order.
     *
     * @param string $quotationId The quotation ID to convert
     * @param array $orderData Additional order-specific data:
     *                          - payment_term: PaymentTerm enum value (default: NET_30)
     *                          - shipping_address: string|null
     *                          - billing_address: string|null
     *                          - customer_po: string|null
     *                          - notes: string|null
     *                          - salesperson_id: string|null
     *                          - preferred_warehouse_id: string|null
     * @return SalesOrderInterface The created sales order
     * @throws QuotationNotFoundException If quotation is not found
     * @throws InvalidQuoteStatusException If quotation cannot be converted
     */
    public function convertToOrder(string $quotationId, array $orderData = []): SalesOrderInterface
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        if (!$quotation->getStatus()->canBeConverted()) {
            throw InvalidQuoteStatusException::cannotConvert($quotationId, $quotation->getStatus());
        }

        // Generate unique order number
        $orderNumber = $this->sequenceGenerator->generate(
            $quotation->getTenantId(),
            'sales_order',
            ['prefix' => 'SO']
        );

        // Determine payment term
        $paymentTerm = $this->resolvePaymentTerm($orderData['payment_term'] ?? null);
        $orderDate = new DateTimeImmutable();

        // Build order data from quotation
        $orderDataBuilt = [
            'tenant_id' => $quotation->getTenantId(),
            'order_number' => $orderNumber,
            'customer_id' => $quotation->getCustomerId(),
            'order_date' => $orderDate,
            'status' => SalesOrderStatus::DRAFT,
            'currency_code' => $quotation->getCurrencyCode(),
            'exchange_rate' => null,
            'subtotal' => $quotation->getSubtotal(),
            'tax_amount' => $quotation->getTaxAmount(),
            'discount_amount' => $quotation->getDiscountAmount(),
            'total' => $quotation->getTotal(),
            'discount_rule' => $quotation->getDiscountRule(),
            'payment_term' => $paymentTerm,
            'payment_due_date' => $paymentTerm->calculateDueDate($orderDate),
            'shipping_address' => $orderData['shipping_address'] ?? null,
            'billing_address' => $orderData['billing_address'] ?? null,
            'customer_po' => $orderData['customer_po'] ?? null,
            'notes' => $orderData['notes'] ?? $quotation->getNotes(),
            'source_quotation_id' => $quotation->getId(),
            'source_quote_number' => $quotation->getQuoteNumber(),
            'salesperson_id' => $orderData['salesperson_id'] ?? null,
            'preferred_warehouse_id' => $orderData['preferred_warehouse_id'] ?? null,
        ];

        // Convert quotation lines to order lines
        $orderLines = [];
        $lineSequence = 1;
        foreach ($quotation->getLines() as $line) {
            $orderLines[] = [
                'product_variant_id' => $line->getProductVariantId(),
                'quantity' => $line->getQuantity(),
                'uom_code' => $line->getUomCode(),
                'unit_price' => $line->getUnitPrice(),
                'line_subtotal' => $line->getLineSubtotal(),
                'tax_amount' => $line->getTaxAmount(),
                'discount_amount' => $line->getDiscountAmount(),
                'line_total' => $line->getLineTotal(),
                'discount_rule' => $line->getDiscountRule(),
                'line_notes' => $line->getLineNotes(),
                'line_sequence' => $lineSequence++,
            ];
        }

        // Build order data object
        $orderData = new SalesOrderData(
            tenantId: $quotation->getTenantId(),
            customerId: $quotation->getCustomerId(),
            currencyCode: $quotation->getCurrencyCode(),
            quoteDate: $orderDate->format('Y-m-d'),
            lines: $orderLines,
            orderNumber: $orderNumber,
            warehouseId: $orderData['preferred_warehouse_id'] ?? null,
            salespersonId: $orderData['salesperson_id'] ?? null,
            shippingAddressId: $orderData['shipping_address_id'] ?? null,
            billingAddressId: $orderData['billing_address_id'] ?? null,
            paymentTerm: $orderData['payment_term'] ?? null,
            customerPoNumber: $orderData['customer_po'] ?? null,
            customerNotes: $orderData['notes'] ?? $quotation->getNotes(),
            internalNotes: $orderData['internal_notes'] ?? null,
            exchangeRate: null,
            metadata: [
                'subtotal' => $quotation->getSubtotal(),
                'tax_amount' => $quotation->getTaxAmount(),
                'discount_amount' => $quotation->getDiscountAmount(),
                'total' => $quotation->getTotal(),
                'source_quotation_id' => $quotation->getId(),
                'source_quote_number' => $quotation->getQuoteNumber(),
                'payment_due_date' => $paymentTerm->calculateDueDate($orderDate)->format('Y-m-d'),
            ],
        );

        $this->logger->info('Converting quotation to order', [
            'quotation_id' => $quotationId,
            'quote_number' => $quotation->getQuoteNumber(),
            'order_number' => $orderNumber,
        ]);

        // Wrap repository calls in transaction for atomicity
        $salesOrder = $this->transactionManager->wrap(function () use ($orderData, $quotationId, $quotation, $orderNumber): SalesOrderInterface {
            // Create and save the sales order via repository
            $salesOrder = $this->salesOrderRepository->create($orderData);

            // Update quotation status to CONVERTED_TO_ORDER and link to order
            $this->quotationRepository->markAsConvertedToOrder($quotationId, $salesOrder->getId());

            // Log audit event inside transaction for consistency
            $this->auditLogger->log(
                $quotation->getTenantId(),
                'quotation_converted',
                "Quotation {$quotation->getQuoteNumber()} converted to order {$orderNumber}",
                [
                    'quotation_id' => $quotationId,
                    'order_id' => $salesOrder->getId(),
                    'order_number' => $orderNumber,
                    'customer_id' => $quotation->getCustomerId(),
                    'total' => $salesOrder->getTotal(),
                    'currency' => $salesOrder->getCurrencyCode(),
                ]
            );

            return $salesOrder;
        });

        $this->logger->info('Quotation converted to order successfully', [
            'quotation_id' => $quotationId,
            'order_id' => $salesOrder->getId(),
            'order_number' => $orderNumber,
            'customer_id' => $quotation->getCustomerId(),
        ]);

        return $salesOrder;
    }

    /**
     * Validate if quotation can be converted.
     *
     * @param string $quotationId
     * @return bool
     * @throws QuotationNotFoundException
     */
    public function canConvertToOrder(string $quotationId): bool
    {
        $quotation = $this->quotationRepository->findById($quotationId);
        
        return $quotation->getStatus()->canBeConverted() 
            && $quotation->getConvertedToOrderId() === null;
    }
}
