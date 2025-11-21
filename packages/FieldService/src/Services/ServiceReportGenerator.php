<?php

declare(strict_types=1);

namespace Nexus\FieldService\Services;

use Nexus\FieldService\Contracts\WorkOrderInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\Contracts\PartsConsumptionRepositoryInterface;
use Nexus\FieldService\Contracts\ServiceReportInterface;
use Nexus\FieldService\Contracts\ServiceReportFactoryInterface;
use Nexus\FieldService\Exceptions\WorkOrderNotFoundException;
use Nexus\Document\Contracts\DocumentManagerInterface;
use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\Enums\DocumentFormat;
use Psr\Log\LoggerInterface;

/**
 * Service Report Generator Service
 *
 * Generates PDF service reports after work order completion.
 * Per FUN-FIE-0269: Auto-generates report with photos, parts, labor, and signature.
 */
final readonly class ServiceReportGenerator
{
    public function __construct(
        private WorkOrderRepositoryInterface $workOrderRepository,
        private PartsConsumptionRepositoryInterface $partsConsumptionRepository,
        private ServiceReportFactoryInterface $serviceReportFactory,
        private DocumentManagerInterface $documentManager,
        private ContentProcessorInterface $contentProcessor,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Generate service report PDF for a completed work order.
     */
    public function generate(string $workOrderId): ServiceReportInterface
    {
        $workOrder = $this->workOrderRepository->findById($workOrderId);

        if ($workOrder === null) {
            throw new WorkOrderNotFoundException("Work order not found: {$workOrderId}");
        }

        // Collect data for report
        $data = $this->collectReportData($workOrder);

        // Render PDF
        $pdfContent = $this->contentProcessor->render(
            'service_report_template',
            $data,
            DocumentFormat::PDF
        );

        // Store the PDF document
        $documentPath = $this->documentManager->store(
            $pdfContent,
            "service_report_{$workOrder->getNumber()->toString()}.pdf",
            DocumentFormat::PDF
        );

        // Create service report entity via factory
        $serviceReport = $this->serviceReportFactory->create([
            'work_order_id' => $workOrderId,
            'document_path' => $documentPath,
            'generated_at' => new \DateTimeImmutable(),
            'metadata' => [
                'work_order_number' => $workOrder->getNumber()->toString(),
                'total_cost' => ($data['labor_cost'] ?? 0) + ($data['total_parts_cost'] ?? 0),
            ],
        ]);

        $this->logger->info('Service report generated', [
            'work_order_id' => $workOrderId,
            'work_order_number' => $workOrder->getNumber()->toString(),
            'document_path' => $documentPath,
        ]);

        return $serviceReport;
    }

    /**
     * Collect all data needed for the service report.
     *
     * @return array<string, mixed>
     */
    private function collectReportData(WorkOrderInterface $workOrder): array
    {
        $partsConsumed = $this->partsConsumptionRepository->findByWorkOrder($workOrder->getId());
        $totalPartsCost = $this->partsConsumptionRepository->getTotalCost($workOrder->getId());

        return [
            'work_order_number' => $workOrder->getNumber()->toString(),
            'service_date' => $workOrder->getActualEnd()?->format('Y-m-d'),
            'service_type' => $workOrder->getServiceType()->label(),
            'priority' => $workOrder->getPriority()->label(),
            'description' => $workOrder->getDescription(),
            'labor_hours' => $workOrder->getLaborHours()?->getHours() ?? 0,
            'labor_cost' => $workOrder->getLaborHours()?->getTotalCost(),
            'parts_consumed' => $partsConsumed,
            'total_parts_cost' => $totalPartsCost,
            'technician_notes' => $workOrder->getTechnicianNotes(),
            // Additional fields will be added from application layer
            // - customer_name (from Party)
            // - technician_name (from Staff)
            // - before_photos (from Document)
            // - after_photos (from Document)
            // - signature_image (from CustomerSignature)
        ];
    }
}
