<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\ProcessingActivity;
use Nexus\DataPrivacy\Enums\LawfulBasisType;
use Nexus\DataPrivacy\Enums\DataCategory;

/**
 * Read operations for processing activities (CQRS Query Model).
 * Supports ROPA (Record of Processing Activities) requirements.
 */
interface ProcessingActivityQueryInterface
{
    /**
     * Find activity by ID.
     */
    public function findById(string $id): ?ProcessingActivity;

    /**
     * Find all processing activities.
     *
     * @return array<ProcessingActivity>
     */
    public function findAll(): array;

    /**
     * Find active processing activities.
     *
     * @return array<ProcessingActivity>
     */
    public function findActive(): array;

    /**
     * Find activities by lawful basis.
     *
     * @return array<ProcessingActivity>
     */
    public function findByLawfulBasis(LawfulBasisType $lawfulBasis): array;

    /**
     * Find activities processing specific data category.
     *
     * @return array<ProcessingActivity>
     */
    public function findByDataCategory(DataCategory $category): array;

    /**
     * Find activities involving cross-border transfers.
     *
     * @return array<ProcessingActivity>
     */
    public function findWithCrossBorderTransfer(): array;

    /**
     * Find activities with automated decision making.
     *
     * @return array<ProcessingActivity>
     */
    public function findWithAutomatedDecisionMaking(): array;

    /**
     * Find activities requiring DPIA.
     *
     * @return array<ProcessingActivity>
     */
    public function findRequiringDpia(): array;

    /**
     * Find activities needing review (not reviewed in given months).
     *
     * @return array<ProcessingActivity>
     */
    public function findNeedingReview(int $monthsSinceReview = 12): array;

    /**
     * Find activities by controller.
     *
     * @return array<ProcessingActivity>
     */
    public function findByController(string $controllerName): array;

    /**
     * Get ROPA summary for compliance reporting.
     *
     * @return array{
     *     total_activities: int,
     *     by_lawful_basis: array<string, int>,
     *     with_cross_border: int,
     *     with_automated_decisions: int,
     *     requiring_dpia: int,
     *     sensitive_data_activities: int
     * }
     */
    public function getRopaSummary(): array;

    /**
     * Export ROPA as array for reporting.
     *
     * @return array<array<string, mixed>>
     */
    public function exportRopa(): array;
}
