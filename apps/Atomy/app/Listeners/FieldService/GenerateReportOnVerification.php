<?php

declare(strict_types=1);

namespace App\Listeners\FieldService;

use Nexus\FieldService\Events\WorkOrderVerifiedEvent;
use Nexus\FieldService\Services\ServiceReportGenerator;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Document\Contracts\DocumentManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service Report Generation and Notification Listener
 *
 * When a work order is verified, generate the final service report PDF
 * and send it to the customer
 *
 * This implements FUN-FIE-0082: Automated service report generation and delivery
 */
final readonly class GenerateReportOnVerification
{
    public function __construct(
        private ServiceReportGenerator $reportGenerator,
        private DocumentManagerInterface $documentManager,
        private NotificationManagerInterface $notificationManager,
        private LoggerInterface $logger
    ) {}

    public function handle(WorkOrderVerifiedEvent $event): void
    {
        try {
            $workOrderId = $event->getWorkOrderId();

            // Generate PDF report
            $report = $this->reportGenerator->generate($workOrderId);

            // Store document
            $document = $this->documentManager->store([
                'filename' => "service_report_{$event->getWorkOrderNumber()}.pdf",
                'mime_type' => 'application/pdf',
                'content' => $report->getPdfContent(),
                'metadata' => [
                    'source' => 'field_service',
                    'work_order_id' => $workOrderId,
                    'work_order_number' => $event->getWorkOrderNumber(),
                    'generated_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
                ],
            ]);

            // Send email to customer with PDF attachment
            $this->notificationManager->send([
                'channel' => 'email',
                'template' => 'field_service.service_complete',
                'recipients' => [$this->getCustomerEmail($event->getCustomerPartyId())],
                'data' => [
                    'work_order_number' => $event->getWorkOrderNumber(),
                    'verified_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
                ],
                'attachments' => [$document->getId()],
            ]);

            $this->logger->info('Service report generated and sent to customer', [
                'work_order_id' => $workOrderId,
                'work_order_number' => $event->getWorkOrderNumber(),
                'document_id' => $document->getId(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate service report', [
                'work_order_id' => $event->getWorkOrderId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getCustomerEmail(string $customerPartyId): string
    {
        // TODO: Fetch from Nexus\Party
        return 'customer@example.com';
    }
}
