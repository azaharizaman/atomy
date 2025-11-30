<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\WorkOrderManagerInterface;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryInterface;
use Nexus\Manufacturing\Contracts\WorkOrderInterface;
use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Exceptions\WorkOrderNotFoundException;
use Nexus\Manufacturing\Exceptions\InvalidWorkOrderStatusException;
use Nexus\Manufacturing\ValueObjects\WorkOrderLine;
use Nexus\Manufacturing\ValueObjects\OperationCompletion;

/**
 * Work Order Manager implementation.
 *
 * Manages work order lifecycle, material issues, and operation completions.
 */
final readonly class WorkOrderManager implements WorkOrderManagerInterface
{
    public function __construct(
        private WorkOrderRepositoryInterface $repository,
        private BomManagerInterface $bomManager,
        private RoutingManagerInterface $routingManager,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        string $productId,
        float $quantity,
        \DateTimeImmutable $plannedStartDate,
        \DateTimeImmutable $plannedEndDate,
        ?string $bomId = null,
        ?string $routingId = null,
        ?string $sourceReference = null
    ): WorkOrderInterface {
        // Get BOM lines for materials
        $materialLines = $this->generateMaterialLines($productId, $quantity);

        // Get routing operations
        $operationLines = $this->generateOperationLines($productId, $quantity);

        $lines = [...$materialLines, ...$operationLines];

        return $this->repository->create([
            'productId' => $productId,
            'quantity' => $quantity,
            'plannedStartDate' => $plannedStartDate->format('Y-m-d'),
            'plannedEndDate' => $plannedEndDate->format('Y-m-d'),
            'bomId' => $bomId,
            'routingId' => $routingId,
            'sourceReference' => $sourceReference,
            'status' => WorkOrderStatus::PLANNED->value,
            'lines' => array_map(fn (WorkOrderLine $line) => $line->toArray(), $lines),
            'completedQuantity' => 0.0,
            'scrapQuantity' => 0.0,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getById(string $id): WorkOrderInterface
    {
        $workOrder = $this->repository->findById($id);

        if ($workOrder === null) {
            throw WorkOrderNotFoundException::withId($id);
        }

        return $workOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getByNumber(string $number): WorkOrderInterface
    {
        $workOrder = $this->repository->findByNumber($number);

        if ($workOrder === null) {
            throw WorkOrderNotFoundException::withNumber($number);
        }

        return $workOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function release(string $workOrderId): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if ($currentStatus !== WorkOrderStatus::PLANNED) {
            throw InvalidWorkOrderStatusException::invalidTransition(
                $workOrderId,
                $currentStatus,
                WorkOrderStatus::RELEASED
            );
        }

        $this->repository->update($workOrderId, [
            'status' => WorkOrderStatus::RELEASED->value,
            'releasedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function start(string $workOrderId, ?\DateTimeImmutable $actualStartDate = null): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if ($currentStatus !== WorkOrderStatus::RELEASED) {
            throw InvalidWorkOrderStatusException::invalidTransition(
                $workOrderId,
                $currentStatus,
                WorkOrderStatus::IN_PROGRESS
            );
        }

        $this->repository->update($workOrderId, [
            'status' => WorkOrderStatus::IN_PROGRESS->value,
            'actualStartDate' => ($actualStartDate ?? new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function complete(string $workOrderId, ?\DateTimeImmutable $actualEndDate = null): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if ($currentStatus !== WorkOrderStatus::IN_PROGRESS) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'complete',
                $currentStatus
            );
        }

        $this->repository->update($workOrderId, [
            'status' => WorkOrderStatus::COMPLETED->value,
            'actualEndDate' => ($actualEndDate ?? new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function close(string $workOrderId): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if (!in_array($currentStatus, [WorkOrderStatus::COMPLETED, WorkOrderStatus::IN_PROGRESS], true)) {
            throw InvalidWorkOrderStatusException::invalidTransition(
                $workOrderId,
                $currentStatus,
                WorkOrderStatus::CLOSED
            );
        }

        $this->repository->update($workOrderId, [
            'status' => WorkOrderStatus::CLOSED->value,
            'closedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(string $workOrderId, string $reason): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if (!$currentStatus->canCancel()) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'cancel',
                $currentStatus
            );
        }

        $this->repository->update($workOrderId, [
            'status' => WorkOrderStatus::CANCELLED->value,
            'cancelledAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'cancellationReason' => $reason,
        ]);
    }

    /**
     * Put work order on hold.
     */
    public function putOnHold(string $workOrderId, string $reason): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if (!in_array($currentStatus, [WorkOrderStatus::RELEASED, WorkOrderStatus::IN_PROGRESS], true)) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'put on hold',
                $currentStatus
            );
        }

        $this->repository->update($workOrderId, [
            'status' => WorkOrderStatus::ON_HOLD->value,
            'holdReason' => $reason,
            'previousStatus' => $currentStatus->value,
        ]);
    }

    /**
     * Resume work order from hold.
     */
    public function resumeFromHold(string $workOrderId): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if ($currentStatus !== WorkOrderStatus::ON_HOLD) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'resume',
                $currentStatus
            );
        }

        // Resume to previous status or IN_PROGRESS
        $previousStatus = $workOrder->getPreviousStatus() ?? WorkOrderStatus::IN_PROGRESS;

        $this->repository->update($workOrderId, [
            'status' => $previousStatus->value,
            'holdReason' => null,
            'previousStatus' => null,
        ]);
    }

    /**
     * Issue material to work order.
     */
    public function issueMaterial(string $workOrderId, int $lineNumber, float $quantity, ?string $lotNumber = null): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if (!$currentStatus->canIssueMaterial()) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'issue material',
                $currentStatus
            );
        }

        $lines = $workOrder->getLines();
        $updated = false;

        foreach ($lines as $index => $line) {
            if ($line->lineNumber === $lineNumber && $line->isMaterial()) {
                $lines[$index] = $line->withIssuedQuantity($line->issuedQuantity + $quantity);
                if ($lotNumber !== null) {
                    // Store lot info in notes or separate tracking
                }
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            throw new \InvalidArgumentException("Material line {$lineNumber} not found");
        }

        $this->repository->update($workOrderId, [
            'lines' => array_map(fn (WorkOrderLine $l) => $l->toArray(), $lines),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function reportOperation(string $workOrderId, OperationCompletion $completion): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if (!in_array($currentStatus, [WorkOrderStatus::RELEASED, WorkOrderStatus::IN_PROGRESS], true)) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'report operation completion',
                $currentStatus
            );
        }

        // Update to IN_PROGRESS if first operation completion
        $updates = [];
        if ($currentStatus === WorkOrderStatus::RELEASED) {
            $updates['status'] = WorkOrderStatus::IN_PROGRESS->value;
            $updates['actualStartDate'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        }

        $lines = $workOrder->getLines();

        foreach ($lines as $index => $line) {
            if ($line->operationNumber === $completion->operationNumber && $line->isOperation()) {
                $lines[$index] = $line
                    ->withIssuedQuantity($line->issuedQuantity + $completion->quantityCompleted)
                    ->withActualHours(
                        $line->actualSetupHours + $completion->setupHours,
                        $line->actualRunHours + $completion->runHours
                    );
                break;
            }
        }

        $updates['lines'] = array_map(fn (WorkOrderLine $l) => $l->toArray(), $lines);
        $updates['completedQuantity'] = $workOrder->getCompletedQuantity() + $completion->quantityCompleted;
        $updates['scrapQuantity'] = $workOrder->getScrapQuantity() + $completion->scrapQuantity;

        $this->repository->update($workOrderId, $updates);
    }

    /**
     * Reschedule work order.
     */
    public function reschedule(string $workOrderId, \DateTimeImmutable $newStartDate, \DateTimeImmutable $newEndDate): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if (!$currentStatus->canReschedule()) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'reschedule',
                $currentStatus
            );
        }

        $this->repository->update($workOrderId, [
            'plannedStartDate' => $newStartDate->format('Y-m-d'),
            'plannedEndDate' => $newEndDate->format('Y-m-d'),
        ]);
    }

    /**
     * Change work order quantity.
     */
    public function changeQuantity(string $workOrderId, float $newQuantity): void
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if (!$currentStatus->canModify()) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'change quantity',
                $currentStatus
            );
        }

        if ($newQuantity < $workOrder->getCompletedQuantity()) {
            throw new \InvalidArgumentException(
                "New quantity ({$newQuantity}) cannot be less than completed quantity ({$workOrder->getCompletedQuantity()})"
            );
        }

        // Regenerate lines with new quantity
        $materialLines = $this->generateMaterialLines($workOrder->getProductId(), $newQuantity);
        $operationLines = $this->generateOperationLines($workOrder->getProductId(), $newQuantity);
        $lines = [...$materialLines, ...$operationLines];

        $this->repository->update($workOrderId, [
            'quantity' => $newQuantity,
            'lines' => array_map(fn (WorkOrderLine $line) => $line->toArray(), $lines),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function reportMaterialConsumption(
        string $workOrderId,
        string $productId,
        float $quantity,
        ?string $lotNumber = null,
        ?string $warehouseId = null
    ): void {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if (!in_array($currentStatus, [WorkOrderStatus::RELEASED, WorkOrderStatus::IN_PROGRESS], true)) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'report material consumption',
                $currentStatus
            );
        }

        $lines = $workOrder->getLines();
        $updated = false;

        foreach ($lines as $index => $line) {
            if ($line->isMaterial() && $line->productId === $productId) {
                $lines[$index] = $line->withIssuedQuantity($line->issuedQuantity + $quantity);
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            throw new \InvalidArgumentException("Material {$productId} not found in work order");
        }

        $this->repository->update($workOrderId, [
            'lines' => array_map(fn (WorkOrderLine $l) => $l->toArray(), $lines),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function reportOutput(
        string $workOrderId,
        float $quantity,
        float $scrapQuantity = 0.0,
        ?string $warehouseId = null
    ): void {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if ($currentStatus !== WorkOrderStatus::IN_PROGRESS) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'report output',
                $currentStatus
            );
        }

        $newCompletedQuantity = $workOrder->getCompletedQuantity() + $quantity;
        $newScrapQuantity = $workOrder->getScrapQuantity() + $scrapQuantity;

        $this->repository->update($workOrderId, [
            'completedQuantity' => $newCompletedQuantity,
            'scrapQuantity' => $newScrapQuantity,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function split(string $workOrderId, float $splitQuantity): WorkOrderInterface
    {
        $workOrder = $this->getById($workOrderId);
        $currentStatus = $workOrder->getStatus();

        if (!in_array($currentStatus, [WorkOrderStatus::PLANNED, WorkOrderStatus::RELEASED], true)) {
            throw InvalidWorkOrderStatusException::cannotPerformAction(
                $workOrderId,
                'split',
                $currentStatus
            );
        }

        if ($splitQuantity >= $workOrder->getQuantity()) {
            throw new \InvalidArgumentException('Split quantity must be less than original quantity');
        }

        // Create new work order with split quantity
        $newWorkOrder = $this->create(
            $workOrder->getProductId(),
            $splitQuantity,
            $workOrder->getPlannedStartDate(),
            $workOrder->getPlannedEndDate()
        );

        // Reduce original work order quantity
        $remainingQuantity = $workOrder->getQuantity() - $splitQuantity;
        $this->changeQuantity($workOrderId, $remainingQuantity);

        return $newWorkOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateVariance(string $workOrderId): array
    {
        $workOrder = $this->getById($workOrderId);

        $plannedMaterial = 0.0;
        $actualMaterial = 0.0;
        $plannedLabor = 0.0;
        $actualLabor = 0.0;

        foreach ($workOrder->getLines() as $line) {
            if ($line->isMaterial()) {
                $plannedMaterial += $line->plannedQuantity;
                $actualMaterial += $line->issuedQuantity;
            } elseif ($line->isOperation()) {
                $plannedLabor += $line->plannedSetupHours + $line->plannedRunHours;
                $actualLabor += $line->actualSetupHours + $line->actualRunHours;
            }
        }

        return [
            'material' => $actualMaterial - $plannedMaterial,
            'labor' => $actualLabor - $plannedLabor,
            'overhead' => 0.0, // Would need cost data
            'total' => ($actualMaterial - $plannedMaterial) + ($actualLabor - $plannedLabor),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(string $workOrderId): array
    {
        $workOrder = $this->getById($workOrderId);

        $completedOperations = 0;
        $totalOperations = 0;

        foreach ($workOrder->getLines() as $line) {
            if ($line->isOperation()) {
                $totalOperations++;
                if ($line->isComplete()) {
                    $completedOperations++;
                }
            }
        }

        $producedQuantity = $workOrder->getCompletedQuantity();
        $plannedQuantity = $workOrder->getQuantity();

        return [
            'completedOperations' => $completedOperations,
            'totalOperations' => $totalOperations,
            'producedQuantity' => $producedQuantity,
            'plannedQuantity' => $plannedQuantity,
            'scrapQuantity' => $workOrder->getScrapQuantity(),
            'percentComplete' => $plannedQuantity > 0 ? ($producedQuantity / $plannedQuantity) * 100 : 0,
        ];
    }

    /**
     * Get material shortages for work order.
     */
    public function getMaterialShortages(string $workOrderId): array
    {
        $workOrder = $this->getById($workOrderId);
        $shortages = [];

        foreach ($workOrder->getLines() as $line) {
            if ($line->isMaterial()) {
                $remaining = $line->getRemainingQuantity();
                if ($remaining > 0) {
                    $shortages[] = [
                        'productId' => $line->productId,
                        'lineNumber' => $line->lineNumber,
                        'plannedQuantity' => $line->plannedQuantity,
                        'issuedQuantity' => $line->issuedQuantity,
                        'remainingQuantity' => $remaining,
                        'uomCode' => $line->uomCode,
                    ];
                }
            }
        }

        return $shortages;
    }

    /**
     * Get operation progress for work order.
     */
    public function getOperationProgress(string $workOrderId): array
    {
        $workOrder = $this->getById($workOrderId);
        $progress = [];

        foreach ($workOrder->getLines() as $line) {
            if ($line->isOperation()) {
                $progress[] = [
                    'operationNumber' => $line->operationNumber,
                    'workCenterId' => $line->workCenterId,
                    'plannedQuantity' => $line->plannedQuantity,
                    'completedQuantity' => $line->issuedQuantity,
                    'completionPercentage' => $line->getCompletionPercentage(),
                    'plannedHours' => $line->plannedSetupHours + $line->plannedRunHours,
                    'actualHours' => $line->actualSetupHours + $line->actualRunHours,
                    'laborEfficiency' => $line->getLaborEfficiency(),
                    'isComplete' => $line->isComplete(),
                ];
            }
        }

        return $progress;
    }

    /**
     * Find work orders by status.
     */
    public function findByStatus(WorkOrderStatus $status, ?string $productId = null): array
    {
        return $this->repository->findByStatus($status, $productId);
    }

    /**
     * Find work orders by date range.
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, ?WorkOrderStatus $status = null): array
    {
        return $this->repository->findByDateRange($startDate, $endDate, $status);
    }

    /**
     * Generate material lines from BOM.
     *
     * @return array<WorkOrderLine>
     */
    private function generateMaterialLines(string $productId, float $quantity): array
    {
        $lines = [];
        $lineNumber = 1;

        try {
            $bom = $this->bomManager->findByProductId($productId);
            foreach ($bom->getLines() as $bomLine) {
                if ($bomLine->isEffectiveAt(new \DateTimeImmutable())) {
                    $requiredQuantity = $bomLine->getQuantityWithScrap() * $quantity;

                    $lines[] = new WorkOrderLine(
                        lineNumber: $lineNumber++,
                        lineType: 'material',
                        productId: $bomLine->productId,
                        plannedQuantity: $requiredQuantity,
                        uomCode: $bomLine->uomCode,
                        operationNumber: $bomLine->operationNumber !== null ? (int) $bomLine->operationNumber : null,
                    );
                }
            }
        } catch (\Exception) {
            // No BOM - product may be procured
        }

        return $lines;
    }

    /**
     * Generate operation lines from routing.
     *
     * @return array<WorkOrderLine>
     */
    private function generateOperationLines(string $productId, float $quantity): array
    {
        $lines = [];
        $lineNumber = 100; // Start at 100 for operations

        try {
            $routing = $this->routingManager->getEffective($productId);
            foreach ($routing->getOperations() as $operation) {
                if ($operation->isEffectiveAt(new \DateTimeImmutable())) {
                    $lines[] = new WorkOrderLine(
                        lineNumber: $lineNumber++,
                        lineType: 'operation',
                        plannedQuantity: $quantity,
                        operationNumber: $operation->operationNumber,
                        workCenterId: $operation->workCenterId,
                        plannedSetupHours: $operation->setupTimeMinutes / 60,
                        plannedRunHours: ($operation->runTimeMinutes * $quantity) / 60,
                    );
                }
            }
        } catch (\Exception) {
            // No routing - product may be procured
        }

        return $lines;
    }
}
