<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Party\Contracts\PartyQueryInterface;
use Nexus\ProcurementOperations\Events\PaymentExecutedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener that notifies vendors when payments are executed.
 *
 * Triggered by: PaymentExecutedEvent from ProcurementOperations
 *
 * Workflow:
 * 1. Receives payment executed event
 * 2. Looks up vendor contact information
 * 3. Sends payment notification via configured channels
 */
final class NotifyVendorOnPaymentExecuted
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ?NotificationManagerInterface $notifier = null,
        private readonly ?PartyQueryInterface $partyQuery = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Handle the payment executed event.
     */
    public function __invoke(PaymentExecutedEvent $event): void
    {
        $this->handle($event);
    }

    /**
     * Handle the payment executed event.
     */
    public function handle(PaymentExecutedEvent $event): void
    {
        if ($this->notifier === null) {
            $this->getLogger()->debug('Notification manager not configured, skipping vendor notification');
            return;
        }

        $this->getLogger()->info('Sending payment notification to vendors', [
            'tenantId' => $event->tenantId,
            'paymentId' => $event->paymentId,
            'invoiceCount' => count($event->vendorBillIds),
        ]);

        // Get unique vendor IDs from the paid invoices
        $vendorIds = $this->getVendorIdsFromInvoices($event);

        foreach ($vendorIds as $vendorId) {
            $this->notifyVendor($vendorId, $event);
        }
    }

    /**
     * Get vendor IDs from paid invoices.
     *
     * @return array<string>
     */
    private function getVendorIdsFromInvoices(PaymentExecutedEvent $event): array
    {
        // Extract unique vendor IDs from the event's vendor bill IDs
        // This requires querying the vendor bills to get their vendor IDs
        if ($this->partyQuery === null) {
            $this->logger->warning('Party query not configured, cannot determine vendor IDs');
            return [];
        }

        // In a real implementation, query vendor bill repository via data provider
        // to get vendor IDs for the paid bills
        // For now, return empty array to prevent placeholder vendor ID issues
        // TODO: Add VendorBillQueryInterface to lookup vendor IDs by bill IDs
        return [];
    }

    /**
     * Send payment notification to a specific vendor.
     */
    private function notifyVendor(string $vendorId, PaymentExecutedEvent $event): void
    {
        try {
            // Get vendor contact information
            $vendorContact = $this->getVendorContact($vendorId, $event->tenantId);

            if ($vendorContact === null) {
                $this->logger->warning('No contact information found for vendor', [
                    'vendorId' => $vendorId,
                ]);
                return;
            }

            // Format payment amount
            $formattedAmount = $this->formatAmount($event->totalAmountCents, $event->currency);

            // Send notification
            $this->notifier->send(
                recipient: $vendorContact['email'] ?? $vendorId,
                channel: 'email',
                template: 'procurement.payment_executed',
                data: [
                    'vendor_name' => $vendorContact['name'] ?? 'Vendor',
                    'payment_id' => $event->paymentId,
                    'payment_reference' => $event->bankReference ?? $event->paymentId,
                    'payment_amount' => $formattedAmount,
                    'currency' => $event->currency,
                    'payment_date' => $event->executedAt->format('Y-m-d'),
                    'invoice_count' => count($event->vendorBillIds),
                    'invoice_numbers' => implode(', ', array_slice($event->vendorBillIds, 0, 5)),
                    'has_more_invoices' => count($event->vendorBillIds) > 5,
                ],
            );

            $this->logger->info('Payment notification sent to vendor', [
                'vendorId' => $vendorId,
                'paymentId' => $event->paymentId,
                'channel' => 'email',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send payment notification to vendor', [
                'vendorId' => $vendorId,
                'paymentId' => $event->paymentId,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - notification failure shouldn't fail the payment
        }
    }

    /**
     * Get vendor contact information.
     *
     * @return array{name: string, email: string}|null
     */
    private function getVendorContact(string $vendorId, string $tenantId): ?array
    {
        if ($this->partyQuery === null) {
            $this->logger->warning('Party query not configured, cannot send vendor notification', [
                'vendorId' => $vendorId,
            ]);
            return null;
        }

        try {
            $party = $this->partyQuery->findById($vendorId);

            if ($party === null) {
                return null;
            }

            return [
                'name' => $party->getName(),
                'email' => $party->getEmail(),
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to fetch vendor contact', [
                'vendorId' => $vendorId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Format amount for display.
     */
    private function formatAmount(int $amountCents, string $currency): string
    {
        return sprintf('%s %s', $currency, number_format($amountCents / 100, 2));
    }
}
