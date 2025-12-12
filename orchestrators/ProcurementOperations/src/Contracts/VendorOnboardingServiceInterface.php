<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorOnboardingRequest;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorOnboardingResult;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorProfileData;
use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * VendorOnboardingServiceInterface - Contract for vendor onboarding operations.
 *
 * This interface defines the operations for managing vendor onboarding,
 * from initial registration through approval and activation.
 */
interface VendorOnboardingServiceInterface
{
    /**
     * Start a new vendor onboarding process.
     *
     * @param VendorOnboardingRequest $request The onboarding request
     * @return VendorOnboardingResult The initial result (may be pending)
     */
    public function startOnboarding(VendorOnboardingRequest $request): VendorOnboardingResult;

    /**
     * Resume an existing onboarding workflow.
     *
     * @param string $workflowId The workflow instance ID
     * @return VendorOnboardingResult The current state
     */
    public function resumeOnboarding(string $workflowId): VendorOnboardingResult;

    /**
     * Submit documents for an onboarding workflow.
     *
     * @param string $workflowId The workflow instance ID
     * @param array<string, array{document_id: string, verified: bool}> $documents Submitted documents
     * @return VendorOnboardingResult The updated result
     */
    public function submitDocuments(string $workflowId, array $documents): VendorOnboardingResult;

    /**
     * Process approval decision for a pending workflow.
     *
     * @param string $workflowId The workflow instance ID
     * @param bool $approved Whether to approve
     * @param string $approvedBy User ID making the decision
     * @param string|null $comments Optional comments
     * @param VendorPortalTier|null $overrideTier Optional tier override
     * @return VendorOnboardingResult The final result
     */
    public function processApproval(
        string $workflowId,
        bool $approved,
        string $approvedBy,
        ?string $comments = null,
        ?VendorPortalTier $overrideTier = null,
    ): VendorOnboardingResult;

    /**
     * Cancel an in-progress onboarding workflow.
     *
     * @param string $workflowId The workflow instance ID
     * @param string $cancelledBy User ID cancelling
     * @param string $reason Cancellation reason
     * @return VendorOnboardingResult The cancelled result
     */
    public function cancelOnboarding(
        string $workflowId,
        string $cancelledBy,
        string $reason,
    ): VendorOnboardingResult;

    /**
     * Get the current status of an onboarding workflow.
     *
     * @param string $workflowId The workflow instance ID
     * @return array{
     *     workflow_id: string,
     *     current_state: string,
     *     vendor_name: string,
     *     created_at: string,
     *     updated_at: string,
     *     pending_items: array<string>,
     * }
     */
    public function getOnboardingStatus(string $workflowId): array;

    /**
     * List onboarding workflows by state.
     *
     * @param string $tenantId Tenant ID
     * @param string|null $state Filter by state (null = all)
     * @param int $limit Maximum results
     * @param int $offset Pagination offset
     * @return array<array{
     *     workflow_id: string,
     *     vendor_name: string,
     *     current_state: string,
     *     created_at: string,
     * }>
     */
    public function listOnboardingWorkflows(
        string $tenantId,
        ?string $state = null,
        int $limit = 50,
        int $offset = 0,
    ): array;
}
