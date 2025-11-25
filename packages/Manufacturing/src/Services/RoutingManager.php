<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Contracts\RoutingRepositoryInterface;
use Nexus\Manufacturing\Contracts\RoutingInterface;
use Nexus\Manufacturing\Exceptions\RoutingNotFoundException;
use Nexus\Manufacturing\Exceptions\InvalidRoutingVersionException;
use Nexus\Manufacturing\ValueObjects\Operation;

/**
 * Routing Manager implementation.
 *
 * Manages manufacturing routings lifecycle, versioning, and calculations.
 */
final readonly class RoutingManager implements RoutingManagerInterface
{
    public function __construct(
        private RoutingRepositoryInterface $repository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        string $productId,
        string $version,
        array $operations = [],
        ?\DateTimeImmutable $effectiveFrom = null,
        ?\DateTimeImmutable $effectiveTo = null
    ): RoutingInterface {
        // Sort operations by operation number
        usort($operations, fn (Operation $a, Operation $b) => $a->operationNumber <=> $b->operationNumber);

        return $this->repository->create([
            'productId' => $productId,
            'version' => $version,
            'operations' => array_map(fn (Operation $op) => $op->toArray(), $operations),
            'effectiveFrom' => $effectiveFrom?->format('Y-m-d'),
            'effectiveTo' => $effectiveTo?->format('Y-m-d'),
            'status' => 'draft',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getById(string $id): RoutingInterface
    {
        $routing = $this->repository->findById($id);

        if ($routing === null) {
            throw RoutingNotFoundException::withId($id);
        }

        return $routing;
    }

    /**
     * {@inheritdoc}
     */
    public function getEffective(string $productId, ?\DateTimeImmutable $asOfDate = null): RoutingInterface
    {
        $routing = $this->repository->findByProductId($productId, $asOfDate);

        if ($routing === null) {
            if ($asOfDate !== null) {
                throw RoutingNotFoundException::noEffectiveRouting($productId, $asOfDate->format('Y-m-d'));
            }
            throw RoutingNotFoundException::withProductId($productId);
        }

        return $routing;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): RoutingInterface
    {
        $routing = $this->getById($id);

        if ($routing->getStatus() === 'released') {
            throw InvalidRoutingVersionException::cannotModify($id, 'released');
        }

        // If updating operations, sort them
        if (isset($data['operations'])) {
            usort($data['operations'], fn (array $a, array $b) =>
                ($a['operationNumber'] ?? 0) <=> ($b['operationNumber'] ?? 0)
            );
        }

        return $this->repository->update($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function addOperation(string $routingId, Operation $operation): void
    {
        $routing = $this->getById($routingId);

        if ($routing->getStatus() === 'released') {
            throw InvalidRoutingVersionException::cannotModify($routingId, 'released');
        }

        $operations = $routing->getOperations();
        $operations[] = $operation;

        // Sort by operation number
        usort($operations, fn (Operation $a, Operation $b) => $a->operationNumber <=> $b->operationNumber);

        $this->repository->update($routingId, [
            'operations' => array_map(fn (Operation $op) => $op->toArray(), $operations),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeOperation(string $routingId, int $operationNumber): void
    {
        $routing = $this->getById($routingId);

        if ($routing->getStatus() === 'released') {
            throw InvalidRoutingVersionException::cannotModify($routingId, 'released');
        }

        $operations = array_filter(
            $routing->getOperations(),
            fn (Operation $op) => $op->operationNumber !== $operationNumber
        );

        $this->repository->update($routingId, [
            'operations' => array_map(fn (Operation $op) => $op->toArray(), array_values($operations)),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function createVersion(string $sourceRoutingId, string $newVersion, ?\DateTimeImmutable $effectiveFrom = null): RoutingInterface
    {
        $sourceRouting = $this->getById($sourceRoutingId);
        $productId = $sourceRouting->getProductId();

        // Check if version already exists
        $existingVersions = $this->repository->findAllVersions($productId);
        foreach ($existingVersions as $existing) {
            if ($existing->getVersion() === $newVersion) {
                throw InvalidRoutingVersionException::versionExists($productId, $newVersion);
            }
        }

        return $this->repository->create([
            'productId' => $productId,
            'version' => $newVersion,
            'operations' => array_map(fn (Operation $op) => $op->toArray(), $sourceRouting->getOperations()),
            'effectiveFrom' => $effectiveFrom?->format('Y-m-d'),
            'status' => 'draft',
            'previousVersionId' => $sourceRoutingId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function release(string $routingId): void
    {
        $routing = $this->getById($routingId);

        if (count($routing->getOperations()) === 0) {
            throw InvalidRoutingVersionException::cannotRelease($routingId, 'Routing has no operations');
        }

        $this->repository->update($routingId, [
            'status' => 'released',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function obsolete(string $routingId): void
    {
        $this->getById($routingId);

        $this->repository->update($routingId, [
            'status' => 'obsolete',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateLeadTime(string $routingId, float $quantity): float
    {
        $routing = $this->getById($routingId);

        $totalMinutes = 0.0;
        $previousOverlap = 0.0;

        foreach ($routing->getOperations() as $operation) {
            $operationTime = $operation->calculateTotalTime($quantity);

            // Apply overlap from previous operation
            $overlapReduction = ($operationTime * $previousOverlap / 100);
            $adjustedTime = max(0, $operationTime - $overlapReduction);

            $totalMinutes += $adjustedTime;
            $previousOverlap = $operation->overlapPercentage;
        }

        // Convert minutes to hours
        return $totalMinutes / 60;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateCost(string $routingId, float $quantity): array
    {
        $routing = $this->getById($routingId);

        $laborCost = 0.0;
        $machineCost = 0.0;
        $overheadCost = 0.0;

        foreach ($routing->getOperations() as $operation) {
            $hours = $operation->getCapacityTimeHours($quantity);
            $laborCost += $hours * ($operation->laborRate ?? 0.0);
            $machineCost += $hours * ($operation->machineRate ?? 0.0);
            $overheadCost += $hours * ($operation->overheadRate ?? 0.0);
        }

        return [
            'labor' => $laborCost,
            'machine' => $machineCost,
            'overhead' => $overheadCost,
            'total' => $laborCost + $machineCost + $overheadCost,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $routingId): array
    {
        $routing = $this->getById($routingId);
        $errors = [];

        // Validate routing has operations
        if (count($routing->getOperations()) === 0) {
            $errors[] = 'Routing must have at least one operation';
        }

        // Validate operation sequence
        $operationNumbers = [];
        foreach ($routing->getOperations() as $operation) {
            if (in_array($operation->operationNumber, $operationNumbers, true)) {
                $errors[] = "Duplicate operation number: {$operation->operationNumber}";
            }
            $operationNumbers[] = $operation->operationNumber;
        }

        return $errors;
    }

    /**
     * Add updateOperation method from interface.
     *
     * {@inheritdoc}
     */
    public function updateOperation(string $routingId, int $operationNumber, Operation $operation): void
    {
        $routing = $this->getById($routingId);

        if ($routing->getStatus() === 'released') {
            throw InvalidRoutingVersionException::cannotModify($routingId, 'released');
        }

        $operations = $routing->getOperations();
        $updated = false;

        foreach ($operations as $index => $existingOp) {
            if ($existingOp->operationNumber === $operationNumber) {
                $operations[$index] = $operation;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            throw new \InvalidArgumentException("Operation number {$operationNumber} not found");
        }

        $this->repository->update($routingId, [
            'operations' => array_map(fn (Operation $op) => $op->toArray(), $operations),
        ]);
    }
}
