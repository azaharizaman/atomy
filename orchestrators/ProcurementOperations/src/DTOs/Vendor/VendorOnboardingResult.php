<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * Vendor onboarding result DTO.
 *
 * Captures the outcome of a vendor onboarding process.
 */
final readonly class VendorOnboardingResult
{
    /**
     * @param bool $isSuccessful Whether onboarding was successful
     * @param string|null $vendorId Created vendor ID if successful
     * @param string|null $portalUserId Created portal user ID if successful
     * @param VendorPortalTier|null $assignedTier Assigned portal tier
     * @param string $status Current status (pending, approved, rejected, etc.)
     * @param array<VendorValidationError> $validationErrors Validation errors
     * @param array<string, mixed> $complianceResults Compliance check results
     * @param string|null $rejectionReason Rejection reason if rejected
     * @param \DateTimeImmutable|null $effectiveDate When vendor becomes active
     * @param array<string> $pendingActions Required actions before approval
     */
    public function __construct(
        public bool $isSuccessful,
        public ?string $vendorId,
        public ?string $portalUserId,
        public ?VendorPortalTier $assignedTier,
        public string $status,
        public array $validationErrors = [],
        public array $complianceResults = [],
        public ?string $rejectionReason = null,
        public ?\DateTimeImmutable $effectiveDate = null,
        public array $pendingActions = [],
    ) {}

    /**
     * Create successful onboarding result.
     */
    public static function success(
        string $vendorId,
        string $portalUserId,
        VendorPortalTier $assignedTier,
        \DateTimeImmutable $effectiveDate,
        array $complianceResults = [],
    ): self {
        return new self(
            isSuccessful: true,
            vendorId: $vendorId,
            portalUserId: $portalUserId,
            assignedTier: $assignedTier,
            status: 'approved',
            validationErrors: [],
            complianceResults: $complianceResults,
            rejectionReason: null,
            effectiveDate: $effectiveDate,
            pendingActions: [],
        );
    }

    /**
     * Create pending approval result.
     *
     * @param array<string> $pendingActions
     */
    public static function pendingApproval(
        string $vendorId,
        array $pendingActions,
        array $complianceResults = [],
    ): self {
        return new self(
            isSuccessful: false,
            vendorId: $vendorId,
            portalUserId: null,
            assignedTier: null,
            status: 'pending_approval',
            validationErrors: [],
            complianceResults: $complianceResults,
            rejectionReason: null,
            effectiveDate: null,
            pendingActions: $pendingActions,
        );
    }

    /**
     * Create pending compliance result.
     *
     * @param array<string> $pendingDocuments
     */
    public static function pendingCompliance(
        string $vendorId,
        array $pendingDocuments,
        array $complianceResults = [],
    ): self {
        return new self(
            isSuccessful: false,
            vendorId: $vendorId,
            portalUserId: null,
            assignedTier: null,
            status: 'pending_compliance',
            validationErrors: [],
            complianceResults: $complianceResults,
            rejectionReason: null,
            effectiveDate: null,
            pendingActions: $pendingDocuments,
        );
    }

    /**
     * Create rejected result.
     *
     * @param array<VendorValidationError> $validationErrors
     */
    public static function rejected(
        string $rejectionReason,
        array $validationErrors = [],
        array $complianceResults = [],
    ): self {
        return new self(
            isSuccessful: false,
            vendorId: null,
            portalUserId: null,
            assignedTier: null,
            status: 'rejected',
            validationErrors: $validationErrors,
            complianceResults: $complianceResults,
            rejectionReason: $rejectionReason,
            effectiveDate: null,
            pendingActions: [],
        );
    }

    /**
     * Create validation failure result.
     *
     * @param array<VendorValidationError> $validationErrors
     */
    public static function validationFailed(
        array $validationErrors,
    ): self {
        return new self(
            isSuccessful: false,
            vendorId: null,
            portalUserId: null,
            assignedTier: null,
            status: 'validation_failed',
            validationErrors: $validationErrors,
            complianceResults: [],
            rejectionReason: 'Validation failed',
            effectiveDate: null,
            pendingActions: [],
        );
    }

    public function isPending(): bool
    {
        return str_starts_with($this->status, 'pending');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function hasValidationErrors(): bool
    {
        return ! empty($this->validationErrors);
    }

    public function hasPendingActions(): bool
    {
        return ! empty($this->pendingActions);
    }

    public function requiresManualReview(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function requiresDocuments(): bool
    {
        return $this->status === 'pending_compliance';
    }

    /**
     * @return array<string>
     */
    public function getBlockingIssues(): array
    {
        $issues = [];

        foreach ($this->validationErrors as $error) {
            if ($error->isBlocking()) {
                $issues[] = $error->message;
            }
        }

        return $issues;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_successful' => $this->isSuccessful,
            'vendor_id' => $this->vendorId,
            'portal_user_id' => $this->portalUserId,
            'assigned_tier' => $this->assignedTier?->value,
            'status' => $this->status,
            'validation_errors' => array_map(
                fn (VendorValidationError $e) => $e->toArray(),
                $this->validationErrors,
            ),
            'compliance_results' => $this->complianceResults,
            'rejection_reason' => $this->rejectionReason,
            'effective_date' => $this->effectiveDate?->format('Y-m-d H:i:s'),
            'pending_actions' => $this->pendingActions,
        ];
    }
}
