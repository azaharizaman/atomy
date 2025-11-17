<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Repository contract for training program persistence operations.
 */
interface TrainingRepositoryInterface
{
    /**
     * Find training program by ID.
     *
     * @param string $id Training ULID
     * @return TrainingInterface|null
     */
    public function findById(string $id): ?TrainingInterface;
    
    /**
     * Get all training programs for tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param array<string, mixed> $filters
     * @return array<TrainingInterface>
     */
    public function getAll(string $tenantId, array $filters = []): array;
    
    /**
     * Get active training programs.
     *
     * @param string $tenantId Tenant ULID
     * @return array<TrainingInterface>
     */
    public function getActiveTrainings(string $tenantId): array;
    
    /**
     * Get upcoming training programs.
     *
     * @param string $tenantId Tenant ULID
     * @param int $daysAhead Number of days ahead
     * @return array<TrainingInterface>
     */
    public function getUpcomingTrainings(string $tenantId, int $daysAhead): array;
    
    /**
     * Create a training program.
     *
     * @param array<string, mixed> $data
     * @return TrainingInterface
     * @throws \Nexus\Hrm\Exceptions\TrainingValidationException
     */
    public function create(array $data): TrainingInterface;
    
    /**
     * Update a training program.
     *
     * @param string $id Training ULID
     * @param array<string, mixed> $data
     * @return TrainingInterface
     * @throws \Nexus\Hrm\Exceptions\TrainingNotFoundException
     * @throws \Nexus\Hrm\Exceptions\TrainingValidationException
     */
    public function update(string $id, array $data): TrainingInterface;
    
    /**
     * Delete a training program.
     *
     * @param string $id Training ULID
     * @return bool
     * @throws \Nexus\Hrm\Exceptions\TrainingNotFoundException
     */
    public function delete(string $id): bool;
}
