<?php

declare(strict_types=1);

namespace Nexus\Compliance\Contracts;

/**
 * Repository interface for SOD violation persistence.
 */
interface SodViolationRepositoryInterface
{
    /**
     * Find a SOD violation by ID.
     *
     * @param string $id The violation ID
     * @return SodViolationInterface|null
     */
    public function findById(string $id): ?SodViolationInterface;

    /**
     * Get all violations for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param \DateTimeImmutable|null $from Optional start date
     * @param \DateTimeImmutable|null $to Optional end date
     * @return array<SodViolationInterface>
     */
    public function getViolations(
        string $tenantId,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array;

    /**
     * Save a SOD violation.
     *
     * @param SodViolationInterface $violation The violation to save
     * @return void
     */
    public function save(SodViolationInterface $violation): void;

    /**
     * Delete a SOD violation.
     *
     * @param string $id The violation ID
     * @return void
     */
    public function delete(string $id): void;
}
