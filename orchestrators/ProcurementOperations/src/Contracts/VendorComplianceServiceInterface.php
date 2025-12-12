<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorProfileData;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorRatingData;
use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * VendorComplianceServiceInterface - Contract for vendor compliance management.
 *
 * This interface defines operations for managing vendor compliance,
 * including sanctions screening, certification tracking, and performance monitoring.
 */
interface VendorComplianceServiceInterface
{
    /**
     * Perform sanctions screening on a vendor.
     *
     * @param string $vendorId Vendor ID
     * @param string $vendorName Vendor name
     * @param string $countryCode Country code
     * @param array<string, mixed> $additionalData Additional screening data
     * @return array{
     *     screened: bool,
     *     matched: bool,
     *     match_lists: array<string>,
     *     confidence: float,
     *     screened_at: string,
     *     requires_review: bool,
     * }
     */
    public function performSanctionsScreening(
        string $vendorId,
        string $vendorName,
        string $countryCode,
        array $additionalData = [],
    ): array;

    /**
     * Check if vendor certifications are current.
     *
     * @param string $vendorId Vendor ID
     * @return array{
     *     is_compliant: bool,
     *     valid_certifications: array<array{name: string, expires: string}>,
     *     expired_certifications: array<array{name: string, expired: string}>,
     *     expiring_soon: array<array{name: string, expires: string, days_until: int}>,
     * }
     */
    public function checkCertificationCompliance(string $vendorId): array;

    /**
     * Get vendor compliance score.
     *
     * @param string $vendorId Vendor ID
     * @return array{
     *     overall_score: float,
     *     components: array{
     *         documentation: float,
     *         certifications: float,
     *         sanctions: float,
     *         performance: float,
     *     },
     *     last_reviewed: string,
     *     next_review_due: string,
     * }
     */
    public function getComplianceScore(string $vendorId): array;

    /**
     * Suspend a vendor.
     *
     * @param string $vendorId Vendor ID
     * @param string $reason Suspension reason
     * @param string $category Suspension category
     * @param string $suspendedBy User suspending
     * @param \DateTimeImmutable|null $suspendUntil Suspension end date (null = indefinite)
     * @return array{
     *     suspended: bool,
     *     suspension_id: string,
     *     suspended_at: string,
     *     suspended_until: ?string,
     * }
     */
    public function suspendVendor(
        string $vendorId,
        string $reason,
        string $category,
        string $suspendedBy,
        ?\DateTimeImmutable $suspendUntil = null,
    ): array;

    /**
     * Reactivate a suspended vendor.
     *
     * @param string $vendorId Vendor ID
     * @param string $reactivatedBy User reactivating
     * @param string $reason Reactivation reason
     * @param array<string, mixed> $conditions Conditions on reactivation
     * @return array{
     *     reactivated: bool,
     *     reactivated_at: string,
     *     conditions: array<string, mixed>,
     * }
     */
    public function reactivateVendor(
        string $vendorId,
        string $reactivatedBy,
        string $reason,
        array $conditions = [],
    ): array;

    /**
     * Update vendor performance rating.
     *
     * @param string $vendorId Vendor ID
     * @param VendorRatingData $rating New rating data
     * @return VendorRatingData Updated rating
     */
    public function updatePerformanceRating(
        string $vendorId,
        VendorRatingData $rating,
    ): VendorRatingData;

    /**
     * Get vendors requiring compliance review.
     *
     * @param string $tenantId Tenant ID
     * @param int $limit Maximum results
     * @return array<array{
     *     vendor_id: string,
     *     vendor_name: string,
     *     review_reason: string,
     *     priority: string,
     *     last_review: ?string,
     * }>
     */
    public function getVendorsRequiringReview(
        string $tenantId,
        int $limit = 50,
    ): array;

    /**
     * Log compliance event.
     *
     * @param string $vendorId Vendor ID
     * @param string $eventType Event type
     * @param array<string, mixed> $eventData Event data
     * @param string $loggedBy User logging
     */
    public function logComplianceEvent(
        string $vendorId,
        string $eventType,
        array $eventData,
        string $loggedBy,
    ): void;

    /**
     * Get compliance history for vendor.
     *
     * @param string $vendorId Vendor ID
     * @param int $limit Maximum results
     * @return array<array{
     *     event_type: string,
     *     event_data: array<string, mixed>,
     *     occurred_at: string,
     *     logged_by: string,
     * }>
     */
    public function getComplianceHistory(
        string $vendorId,
        int $limit = 100,
    ): array;

    /**
     * Check if vendor is blocked for transactions.
     *
     * @param string $vendorId Vendor ID
     * @return array{
     *     is_blocked: bool,
     *     block_reason: ?string,
     *     block_category: ?string,
     *     blocked_since: ?string,
     *     blocked_until: ?string,
     * }
     */
    public function isVendorBlocked(string $vendorId): array;

    /**
     * Request compliance waiver.
     *
     * @param string $vendorId Vendor ID
     * @param string $waiverType Waiver type
     * @param string $justification Justification
     * @param string $requestedBy User requesting
     * @param \DateTimeImmutable|null $waiverUntil Waiver end date
     * @return array{
     *     waiver_id: string,
     *     status: string,
     *     requires_approval: bool,
     *     assigned_approver: ?string,
     * }
     */
    public function requestComplianceWaiver(
        string $vendorId,
        string $waiverType,
        string $justification,
        string $requestedBy,
        ?\DateTimeImmutable $waiverUntil = null,
    ): array;
}
