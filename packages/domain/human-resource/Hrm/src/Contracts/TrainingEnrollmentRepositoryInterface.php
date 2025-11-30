<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Repository contract for training enrollment persistence operations.
 */
interface TrainingEnrollmentRepositoryInterface
{
    /**
     * Find training enrollment by ID.
     *
     * @param string $id Enrollment ULID
     * @return TrainingEnrollmentInterface|null
     */
    public function findById(string $id): ?TrainingEnrollmentInterface;
    
    /**
     * Get enrollment for employee and training.
     *
     * @param string $employeeId Employee ULID
     * @param string $trainingId Training ULID
     * @return TrainingEnrollmentInterface|null
     */
    public function findByEmployeeAndTraining(string $employeeId, string $trainingId): ?TrainingEnrollmentInterface;
    
    /**
     * Get all enrollments for employee.
     *
     * @param string $employeeId Employee ULID
     * @param array<string, mixed> $filters
     * @return array<TrainingEnrollmentInterface>
     */
    public function getEmployeeEnrollments(string $employeeId, array $filters = []): array;
    
    /**
     * Get all enrollments for training program.
     *
     * @param string $trainingId Training ULID
     * @param array<string, mixed> $filters
     * @return array<TrainingEnrollmentInterface>
     */
    public function getTrainingEnrollments(string $trainingId, array $filters = []): array;
    
    /**
     * Create a training enrollment.
     *
     * @param array<string, mixed> $data
     * @return TrainingEnrollmentInterface
     * @throws \Nexus\Hrm\Exceptions\TrainingEnrollmentValidationException
     * @throws \Nexus\Hrm\Exceptions\TrainingEnrollmentDuplicateException
     */
    public function create(array $data): TrainingEnrollmentInterface;
    
    /**
     * Update a training enrollment.
     *
     * @param string $id Enrollment ULID
     * @param array<string, mixed> $data
     * @return TrainingEnrollmentInterface
     * @throws \Nexus\Hrm\Exceptions\TrainingEnrollmentNotFoundException
     * @throws \Nexus\Hrm\Exceptions\TrainingEnrollmentValidationException
     */
    public function update(string $id, array $data): TrainingEnrollmentInterface;
    
    /**
     * Delete a training enrollment.
     *
     * @param string $id Enrollment ULID
     * @return bool
     * @throws \Nexus\Hrm\Exceptions\TrainingEnrollmentNotFoundException
     */
    public function delete(string $id): bool;
}
