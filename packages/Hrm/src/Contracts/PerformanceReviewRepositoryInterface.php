<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Repository contract for performance review persistence operations.
 */
interface PerformanceReviewRepositoryInterface
{
    /**
     * Find performance review by ID.
     *
     * @param string $id Review ULID
     * @return PerformanceReviewInterface|null
     */
    public function findById(string $id): ?PerformanceReviewInterface;
    
    /**
     * Get all reviews for employee.
     *
     * @param string $employeeId Employee ULID
     * @param array<string, mixed> $filters
     * @return array<PerformanceReviewInterface>
     */
    public function getEmployeeReviews(string $employeeId, array $filters = []): array;
    
    /**
     * Get reviews conducted by reviewer.
     *
     * @param string $reviewerId Reviewer's employee ULID
     * @param array<string, mixed> $filters
     * @return array<PerformanceReviewInterface>
     */
    public function getReviewsByReviewer(string $reviewerId, array $filters = []): array;
    
    /**
     * Get pending reviews for reviewer.
     *
     * @param string $reviewerId Reviewer's employee ULID
     * @return array<PerformanceReviewInterface>
     */
    public function getPendingReviewsForReviewer(string $reviewerId): array;
    
    /**
     * Get reviews in date range.
     *
     * @param string $tenantId Tenant ULID
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     * @param array<string, mixed> $filters
     * @return array<PerformanceReviewInterface>
     */
    public function getReviewsInDateRange(
        string $tenantId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $filters = []
    ): array;
    
    /**
     * Create a performance review.
     *
     * @param array<string, mixed> $data
     * @return PerformanceReviewInterface
     * @throws \Nexus\Hrm\Exceptions\PerformanceReviewValidationException
     */
    public function create(array $data): PerformanceReviewInterface;
    
    /**
     * Update a performance review.
     *
     * @param string $id Review ULID
     * @param array<string, mixed> $data
     * @return PerformanceReviewInterface
     * @throws \Nexus\Hrm\Exceptions\PerformanceReviewNotFoundException
     * @throws \Nexus\Hrm\Exceptions\PerformanceReviewValidationException
     */
    public function update(string $id, array $data): PerformanceReviewInterface;
    
    /**
     * Delete a performance review.
     *
     * @param string $id Review ULID
     * @return bool
     * @throws \Nexus\Hrm\Exceptions\PerformanceReviewNotFoundException
     */
    public function delete(string $id): bool;
}
