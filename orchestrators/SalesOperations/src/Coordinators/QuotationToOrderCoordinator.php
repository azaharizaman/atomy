<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Coordinators;

use Nexus\SalesOperations\Contracts\QuotationProviderInterface;
use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\Contracts\AuditLoggerInterface;
use Nexus\SalesOperations\DTOs\ConvertQuotationRequest;
use Nexus\SalesOperations\DTOs\ConvertQuotationResult;
use Psr\Log\LoggerInterface;

final readonly class QuotationToOrderCoordinator
{
    public function __construct(
        private QuotationProviderInterface $quotationProvider,
        private SalesOrderProviderInterface $orderProvider,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    public function convertToOrder(ConvertQuotationRequest $request): ConvertQuotationResult
    {
        $quotation = $this->quotationProvider->findById(
            $request->tenantId,
            $request->quotationId
        );

        if ($quotation === null) {
            return new ConvertQuotationResult(
                success: false,
                message: "Quotation {$request->quotationId} not found"
            );
        }

        if (!$quotation->isConvertible()) {
            return new ConvertQuotationResult(
                success: false,
                message: "Quotation {$request->quotationId} cannot be converted (status: {$quotation->getStatus()})"
            );
        }

        if ($quotation->isExpired()) {
            return new ConvertQuotationResult(
                success: false,
                message: "Quotation {$request->quotationId} has expired"
            );
        }

        $orderData = [
            'customer_id' => $quotation->getCustomerId(),
            'quotation_id' => $quotation->getId(),
            'lines' => $this->buildOrderLines($quotation->getLines()),
            'payment_terms' => $request->overrides['payment_terms'] ?? 'NET_30',
            'shipping_address' => $request->overrides['shipping_address'] ?? null,
            'billing_address' => $request->overrides['billing_address'] ?? null,
            'notes' => $request->notes,
            'created_by' => $request->convertedBy,
        ];

        $order = $this->orderProvider->create($request->tenantId, $orderData);

        $this->quotationProvider->markAsConverted(
            $request->tenantId,
            $quotation->getId(),
            $order->getId()
        );

        $this->auditLogger->log(
            logName: 'sales_quotation_converted',
            description: "Quotation {$quotation->getQuotationNumber()} converted to Order {$order->getOrderNumber()}"
        );

        $this->logger->info("Quotation {$quotation->getId()} converted to Order {$order->getId()}");

        return new ConvertQuotationResult(
            success: true,
            orderId: $order->getId(),
            orderNumber: $order->getOrderNumber()
        );
    }

    private function buildOrderLines(array $quotationLines): array
    {
        $orderLines = [];

        foreach ($quotationLines as $line) {
            $orderLines[] = [
                'product_variant_id' => $line->getProductVariantId(),
                'quantity' => $line->getQuantity(),
                'unit_price' => $line->getUnitPrice(),
                'discount_percent' => $line->getDiscountPercent(),
            ];
        }

        return $orderLines;
    }
}
