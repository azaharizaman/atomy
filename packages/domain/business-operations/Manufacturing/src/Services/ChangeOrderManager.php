<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\ChangeOrderManagerInterface;
use Nexus\Manufacturing\Contracts\ChangeOrderRepositoryInterface;
use Nexus\Manufacturing\Contracts\ChangeOrderInterface;
use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Exceptions\ChangeOrderNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Change Order Manager implementation.
 *
 * Manages engineering change orders (ECOs) for BOMs and Routings.
 */
final readonly class ChangeOrderManager implements ChangeOrderManagerInterface
{
    public function __construct(
        private ChangeOrderRepositoryInterface $repository,
        private BomManagerInterface $bomManager,
        private RoutingManagerInterface $routingManager,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        string $productId,
        string $description,
        array $affectedBomIds = [],
        array $affectedRoutingIds = [],
        ?\DateTimeImmutable $effectiveDate = null
    ): ChangeOrderInterface {
        $data = [
            'productId' => $productId,
            'description' => $description,
            'affectedBomIds' => $affectedBomIds,
            'affectedRoutingIds' => $affectedRoutingIds,
            'effectiveDate' => $effectiveDate,
            'status' => 'draft',
        ];

        $changeOrder = $this->repository->create($data);

        $this->logger?->info('Change order created', [
            'id' => $changeOrder->getId(),
            'productId' => $productId,
            'affectedBomIds' => $affectedBomIds,
            'affectedRoutingIds' => $affectedRoutingIds,
        ]);

        return $changeOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getById(string $id): ChangeOrderInterface
    {
        $changeOrder = $this->repository->findById($id);

        if ($changeOrder === null) {
            throw ChangeOrderNotFoundException::withId($id);
        }

        return $changeOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getByNumber(string $number): ChangeOrderInterface
    {
        $changeOrder = $this->repository->findByNumber($number);

        if ($changeOrder === null) {
            throw ChangeOrderNotFoundException::withNumber($number);
        }

        return $changeOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(string $changeOrderId): void
    {
        $changeOrder = $this->getById($changeOrderId);

        if ($changeOrder->getStatus() !== 'draft') {
            throw new \RuntimeException('Only draft change orders can be submitted');
        }

        $this->repository->update($changeOrderId, [
            'status' => 'pending_approval',
            'submittedAt' => new \DateTimeImmutable(),
        ]);

        $this->logger?->info('Change order submitted for approval', [
            'id' => $changeOrderId,
            'number' => $changeOrder->getOrderNumber(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function approve(string $changeOrderId, string $approvedBy): void
    {
        $changeOrder = $this->getById($changeOrderId);

        if ($changeOrder->getStatus() !== 'pending_approval') {
            throw new \RuntimeException('Only pending change orders can be approved');
        }

        $this->repository->update($changeOrderId, [
            'status' => 'approved',
            'approvedAt' => new \DateTimeImmutable(),
            'approvedBy' => $approvedBy,
        ]);

        $this->logger?->info('Change order approved', [
            'id' => $changeOrderId,
            'approvedBy' => $approvedBy,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function reject(string $changeOrderId, string $rejectedBy, string $reason): void
    {
        $changeOrder = $this->getById($changeOrderId);

        if ($changeOrder->getStatus() !== 'pending_approval') {
            throw new \RuntimeException('Only pending change orders can be rejected');
        }

        $this->repository->update($changeOrderId, [
            'status' => 'rejected',
            'rejectedAt' => new \DateTimeImmutable(),
            'rejectedBy' => $rejectedBy,
            'rejectionReason' => $reason,
        ]);

        $this->logger?->info('Change order rejected', [
            'id' => $changeOrderId,
            'rejectedBy' => $rejectedBy,
            'reason' => $reason,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function implement(string $changeOrderId): void
    {
        $changeOrder = $this->getById($changeOrderId);

        if ($changeOrder->getStatus() !== 'approved') {
            throw new \RuntimeException('Only approved change orders can be implemented');
        }

        // Apply changes to BOMs and/or Routings
        $this->applyChanges($changeOrder);

        $this->repository->update($changeOrderId, [
            'status' => 'implemented',
            'implementedAt' => new \DateTimeImmutable(),
        ]);

        $this->logger?->info('Change order implemented', [
            'id' => $changeOrderId,
            'number' => $changeOrder->getOrderNumber(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(string $changeOrderId, string $reason): void
    {
        $changeOrder = $this->getById($changeOrderId);

        if (in_array($changeOrder->getStatus(), ['implemented', 'cancelled'], true)) {
            throw new \RuntimeException("Cannot cancel change order in status: {$changeOrder->getStatus()}");
        }

        $this->repository->update($changeOrderId, [
            'status' => 'cancelled',
            'cancelledAt' => new \DateTimeImmutable(),
            'cancellationReason' => $reason,
        ]);

        $this->logger?->info('Change order cancelled', [
            'id' => $changeOrderId,
            'reason' => $reason,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function addBomChange(string $changeOrderId, array $change): void
    {
        $changeOrder = $this->getById($changeOrderId);

        if ($changeOrder->getStatus() !== 'draft') {
            throw new \RuntimeException('Can only add changes to draft change orders');
        }

        $existingChanges = $changeOrder->getBomChanges();
        $existingChanges[] = $this->validateBomChange($change);

        $this->repository->update($changeOrderId, ['bomChanges' => $existingChanges]);
    }

    /**
     * {@inheritdoc}
     */
    public function addRoutingChange(string $changeOrderId, array $change): void
    {
        $changeOrder = $this->getById($changeOrderId);

        if ($changeOrder->getStatus() !== 'draft') {
            throw new \RuntimeException('Can only add changes to draft change orders');
        }

        $existingChanges = $changeOrder->getRoutingChanges();
        $existingChanges[] = $this->validateRoutingChange($change);

        $this->repository->update($changeOrderId, ['routingChanges' => $existingChanges]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingForProduct(string $productId): array
    {
        return $this->repository->findPendingByProduct($productId);
    }

    /**
     * {@inheritdoc}
     */
    public function getHistory(string $productId): array
    {
        return $this->repository->findByProduct($productId);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $changeOrderId): array
    {
        $changeOrder = $this->getById($changeOrderId);
        $errors = [];

        // Validate BOM changes
        foreach ($changeOrder->getBomChanges() as $index => $bomChange) {
            $bomId = $bomChange['bomId'] ?? null;
            if ($bomId) {
                try {
                    $this->bomManager->getById($bomId);
                } catch (\Exception) {
                    $errors[] = "BOM change [{$index}]: BOM '{$bomId}' not found";
                }
            }
        }

        // Validate Routing changes
        foreach ($changeOrder->getRoutingChanges() as $index => $routingChange) {
            $routingId = $routingChange['routingId'] ?? null;
            if ($routingId) {
                try {
                    $this->routingManager->getById($routingId);
                } catch (\Exception) {
                    $errors[] = "Routing change [{$index}]: Routing '{$routingId}' not found";
                }
            }
        }

        // Validate effective date
        $effectiveDate = $changeOrder->getEffectiveFrom();
        if ($effectiveDate !== null && $effectiveDate < new \DateTimeImmutable()) {
            $errors[] = 'Effective date cannot be in the past';
        }

        return $errors;
    }

    /**
     * Apply changes from a change order to BOMs and Routings.
     */
    private function applyChanges(ChangeOrderInterface $changeOrder): void
    {
        $effectiveDate = $changeOrder->getEffectiveDate() ?? new \DateTimeImmutable();

        // Apply BOM changes
        foreach ($changeOrder->getBomChanges() as $bomChange) {
            $this->applyBomChange($bomChange, $effectiveDate);
        }

        // Apply Routing changes
        foreach ($changeOrder->getRoutingChanges() as $routingChange) {
            $this->applyRoutingChange($routingChange, $effectiveDate);
        }
    }

    /**
     * Apply a single BOM change.
     */
    private function applyBomChange(array $change, \DateTimeImmutable $effectiveDate): void
    {
        $changeType = $change['changeType'] ?? 'modify';
        $bomId = $change['bomId'] ?? null;

        if (!$bomId) {
            return;
        }

        match ($changeType) {
            'add_line' => $this->bomManager->addLine($bomId, $change['line']),
            'remove_line' => $this->bomManager->removeLine($bomId, $change['lineNumber']),
            'modify_line' => $this->bomManager->updateLine(
                $bomId,
                $change['lineNumber'],
                $change['lineData']
            ),
            'new_version' => $this->bomManager->createNewVersion($bomId, [
                'effectiveFrom' => $effectiveDate,
            ]),
            default => null,
        };
    }

    /**
     * Apply a single Routing change.
     */
    private function applyRoutingChange(array $change, \DateTimeImmutable $effectiveDate): void
    {
        $changeType = $change['changeType'] ?? 'modify';
        $routingId = $change['routingId'] ?? null;

        if (!$routingId) {
            return;
        }

        match ($changeType) {
            'add_operation' => $this->routingManager->addOperation($routingId, $change['operation']),
            'remove_operation' => $this->routingManager->removeOperation(
                $routingId,
                $change['operationNumber']
            ),
            'modify_operation' => $this->routingManager->updateOperation(
                $routingId,
                $change['operationNumber'],
                $change['operationData']
            ),
            'new_version' => $this->routingManager->createNewVersion($routingId, [
                'effectiveFrom' => $effectiveDate,
            ]),
            default => null,
        };
    }

    /**
     * Validate BOM change data.
     */
    private function validateBomChange(array $change): array
    {
        if (empty($change['bomId']) && empty($change['productId'])) {
            throw new \InvalidArgumentException('BOM change must have bomId or productId');
        }

        if (empty($change['changeType'])) {
            throw new \InvalidArgumentException('BOM change must specify changeType');
        }

        return $change;
    }

    /**
     * Validate Routing change data.
     */
    private function validateRoutingChange(array $change): array
    {
        if (empty($change['routingId']) && empty($change['productId'])) {
            throw new \InvalidArgumentException('Routing change must have routingId or productId');
        }

        if (empty($change['changeType'])) {
            throw new \InvalidArgumentException('Routing change must specify changeType');
        }

        return $change;
    }
}
