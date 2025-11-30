<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Exceptions\ChangeOrderNotFoundException;

/**
 * Repository interface for Engineering Change Order persistence.
 *
 * Consumers must implement this interface to provide Change Order storage.
 */
interface ChangeOrderRepositoryInterface
{
    /**
     * Find a change order by ID.
     *
     * @throws ChangeOrderNotFoundException If change order not found
     */
    public function findById(string $id): ChangeOrderInterface;

    /**
     * Find a change order by ID or return null.
     */
    public function findByIdOrNull(string $id): ?ChangeOrderInterface;

    /**
     * Find a change order by number.
     *
     * @throws ChangeOrderNotFoundException If change order not found
     */
    public function findByNumber(string $number): ChangeOrderInterface;

    /**
     * Find all change orders affecting a product.
     *
     * @return array<ChangeOrderInterface>
     */
    public function findByProduct(string $productId): array;

    /**
     * Find all change orders in a specific status.
     *
     * @return array<ChangeOrderInterface>
     */
    public function findByStatus(string $status): array;

    /**
     * Find pending change orders that should become effective.
     *
     * @return array<ChangeOrderInterface>
     */
    public function findPendingEffective(\DateTimeImmutable $asOfDate): array;

    /**
     * Save a change order (create or update).
     */
    public function save(ChangeOrderInterface $changeOrder): void;

    /**
     * Delete a change order by ID.
     */
    public function delete(string $id): void;
}
