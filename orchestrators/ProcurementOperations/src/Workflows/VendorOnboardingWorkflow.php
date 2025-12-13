<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorOnboardingRequest;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorOnboardingResult;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorProfileData;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorValidationError;
use Nexus\ProcurementOperations\Enums\VendorPortalTier;
use Nexus\ProcurementOperations\Events\VendorPortal\VendorOnboardingApprovedEvent;
use Nexus\ProcurementOperations\Events\VendorPortal\VendorOnboardingRejectedEvent;
use Nexus\ProcurementOperations\Events\VendorPortal\VendorRegistrationSubmittedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * VendorOnboardingWorkflow - Stateful saga for vendor onboarding.
 *
 * This workflow orchestrates the complete vendor onboarding process,
 * including validation, compliance checks, approval routing, and activation.
 *
 * States:
 * - INITIATED: Registration submitted, validation pending
 * - VALIDATING: Basic validation in progress
 * - COMPLIANCE_CHECK: Compliance/sanctions screening
 * - PENDING_DOCUMENTS: Waiting for required documents
 * - PENDING_APPROVAL: Awaiting manual approval
 * - APPROVED: Onboarding approved, activation pending
 * - ACTIVATED: Vendor fully activated
 * - REJECTED: Onboarding rejected
 * - CANCELLED: Process cancelled
 *
 * @see ARCHITECTURE.md for workflow patterns
 */
final class VendorOnboardingWorkflow
{
    public const STATE_INITIATED = 'INITIATED';

    public const STATE_VALIDATING = 'VALIDATING';

    public const STATE_COMPLIANCE_CHECK = 'COMPLIANCE_CHECK';

    public const STATE_PENDING_DOCUMENTS = 'PENDING_DOCUMENTS';

    public const STATE_PENDING_APPROVAL = 'PENDING_APPROVAL';

    public const STATE_APPROVED = 'APPROVED';

    public const STATE_ACTIVATED = 'ACTIVATED';

    public const STATE_REJECTED = 'REJECTED';

    public const STATE_CANCELLED = 'CANCELLED';

    /**
     * Valid state transitions.
     *
     * @var array<string, array<string>>
     */
    private const STATE_TRANSITIONS = [
        self::STATE_INITIATED => [self::STATE_VALIDATING, self::STATE_CANCELLED],
        self::STATE_VALIDATING => [self::STATE_COMPLIANCE_CHECK, self::STATE_REJECTED, self::STATE_CANCELLED],
        self::STATE_COMPLIANCE_CHECK => [self::STATE_PENDING_DOCUMENTS, self::STATE_PENDING_APPROVAL, self::STATE_REJECTED, self::STATE_CANCELLED],
        self::STATE_PENDING_DOCUMENTS => [self::STATE_COMPLIANCE_CHECK, self::STATE_CANCELLED],
        self::STATE_PENDING_APPROVAL => [self::STATE_APPROVED, self::STATE_REJECTED, self::STATE_CANCELLED],
        self::STATE_APPROVED => [self::STATE_ACTIVATED, self::STATE_CANCELLED],
        self::STATE_ACTIVATED => [], // Terminal state
        self::STATE_REJECTED => [], // Terminal state
        self::STATE_CANCELLED => [], // Terminal state
    ];

    /**
     * Current workflow state.
     */
    private string $currentState = self::STATE_INITIATED;

    /**
     * Workflow instance ID.
     */
    private string $workflowId;

    /**
     * State transition history.
     *
     * @var array<array{from: string, to: string, timestamp: string, reason: ?string}>
     */
    private array $stateHistory = [];

    /**
     * Validation errors accumulated.
     *
     * @var array<VendorValidationError>
     */
    private array $validationErrors = [];

    /**
     * Compliance check results.
     *
     * @var array<string, mixed>
     */
    private array $complianceResults = [];

    /**
     * Required documents status.
     *
     * @var array<string, array{required: bool, received: bool, verified: bool}>
     */
    private array $documentStatus = [];

    /**
     * Assigned approver (if needed).
     */
    private ?string $assignedApprover = null;

    /**
     * Determined vendor tier.
     */
    private ?VendorPortalTier $determinedTier = null;

    private LoggerInterface $logger;

    public function __construct(
        private readonly VendorOnboardingRequest $request,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->workflowId = $this->generateWorkflowId();
        $this->logger = $logger ?? new NullLogger();
        $this->initializeDocumentRequirements();
    }

    /**
     * Get current workflow state.
     */
    public function getCurrentState(): string
    {
        return $this->currentState;
    }

    /**
     * Get workflow instance ID.
     */
    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    /**
     * Get the onboarding request.
     */
    public function getRequest(): VendorOnboardingRequest
    {
        return $this->request;
    }

    /**
     * Start the onboarding workflow.
     */
    public function start(): VendorOnboardingResult
    {
        $this->logger->info('Starting vendor onboarding workflow', [
            'workflow_id' => $this->workflowId,
            'vendor_name' => $this->request->vendorName,
            'tenant_id' => $this->request->tenantId,
        ]);

        // Dispatch registration submitted event
        $this->dispatchEvent(VendorRegistrationSubmittedEvent::create(
            tenantId: $this->request->tenantId,
            vendorName: $this->request->vendorName,
            countryCode: $this->request->countryCode,
            vendorType: $this->request->vendorType,
        ));

        // Transition to validating
        $this->transitionTo(self::STATE_VALIDATING, 'Workflow started');

        return $this->executeValidation();
    }

    /**
     * Execute basic validation step.
     */
    public function executeValidation(): VendorOnboardingResult
    {
        $this->ensureInState(self::STATE_VALIDATING);

        $this->logger->info('Executing validation step', [
            'workflow_id' => $this->workflowId,
        ]);

        // Validate required fields
        $this->validateRequiredFields();

        // Validate format constraints
        $this->validateFormats();

        // Check for duplicates
        $this->checkDuplicates();

        if ($this->hasValidationErrors()) {
            return $this->handleValidationFailure();
        }

        // Move to compliance check
        $this->transitionTo(self::STATE_COMPLIANCE_CHECK, 'Validation passed');

        return $this->executeComplianceCheck();
    }

    /**
     * Execute compliance screening step.
     */
    public function executeComplianceCheck(): VendorOnboardingResult
    {
        $this->ensureInState(self::STATE_COMPLIANCE_CHECK);

        $this->logger->info('Executing compliance check', [
            'workflow_id' => $this->workflowId,
        ]);

        // Perform sanctions screening
        $sanctionsResult = $this->performSanctionsScreening();
        $this->complianceResults['sanctions'] = $sanctionsResult;

        if ($sanctionsResult['matched']) {
            return $this->handleSanctionsMatch($sanctionsResult);
        }

        // Perform due diligence checks
        $dueDiligenceResult = $this->performDueDiligence();
        $this->complianceResults['due_diligence'] = $dueDiligenceResult;

        // Check if documents are required
        if ($this->hasOutstandingDocuments()) {
            $this->transitionTo(self::STATE_PENDING_DOCUMENTS, 'Documents required');

            return VendorOnboardingResult::pendingCompliance(
                workflowId: $this->workflowId,
                pendingItems: $this->getOutstandingDocumentsList(),
            );
        }

        // Determine if approval is needed
        return $this->routeForApproval();
    }

    /**
     * Submit required documents.
     *
     * @param array<string, array{document_id: string, verified: bool}> $documents
     */
    public function submitDocuments(array $documents): VendorOnboardingResult
    {
        $this->ensureInState(self::STATE_PENDING_DOCUMENTS);

        $this->logger->info('Documents submitted', [
            'workflow_id' => $this->workflowId,
            'document_count' => count($documents),
        ]);

        foreach ($documents as $documentType => $documentInfo) {
            if (isset($this->documentStatus[$documentType])) {
                $this->documentStatus[$documentType]['received'] = true;
                $this->documentStatus[$documentType]['verified'] = $documentInfo['verified'];
            }
        }

        // Check if all documents received
        if (! $this->hasOutstandingDocuments()) {
            $this->transitionTo(self::STATE_COMPLIANCE_CHECK, 'Documents received');

            return $this->executeComplianceCheck();
        }

        return VendorOnboardingResult::pendingCompliance(
            workflowId: $this->workflowId,
            pendingItems: $this->getOutstandingDocumentsList(),
        );
    }

    /**
     * Process approval decision.
     */
    public function processApprovalDecision(
        bool $approved,
        string $approvedBy,
        ?string $comments = null,
        ?VendorPortalTier $overrideTier = null,
    ): VendorOnboardingResult {
        $this->ensureInState(self::STATE_PENDING_APPROVAL);

        $this->logger->info('Processing approval decision', [
            'workflow_id' => $this->workflowId,
            'approved' => $approved,
            'approved_by' => $approvedBy,
        ]);

        if (! $approved) {
            return $this->handleManualRejection($approvedBy, $comments ?? 'Rejected by approver');
        }

        // Override tier if specified
        if ($overrideTier !== null) {
            $this->determinedTier = $overrideTier;
        }

        $this->transitionTo(self::STATE_APPROVED, "Approved by {$approvedBy}");

        // Dispatch approval event
        $this->dispatchEvent(VendorOnboardingApprovedEvent::create(
            tenantId: $this->request->tenantId,
            vendorId: $this->workflowId, // Use workflow ID as vendor ID until activation
            vendorName: $this->request->vendorName,
            approvedBy: $approvedBy,
            assignedTier: $this->determinedTier ?? VendorPortalTier::BASIC,
        ));

        return $this->activate($approvedBy);
    }

    /**
     * Activate the vendor (final step).
     */
    public function activate(string $activatedBy): VendorOnboardingResult
    {
        $this->ensureInState(self::STATE_APPROVED);

        $this->logger->info('Activating vendor', [
            'workflow_id' => $this->workflowId,
            'activated_by' => $activatedBy,
        ]);

        $this->transitionTo(self::STATE_ACTIVATED, "Activated by {$activatedBy}");

        // Generate final vendor ID
        $vendorId = $this->generateVendorId();

        return VendorOnboardingResult::success(
            vendorId: $vendorId,
            assignedTier: $this->determinedTier ?? VendorPortalTier::BASIC,
            workflowId: $this->workflowId,
        );
    }

    /**
     * Cancel the workflow.
     */
    public function cancel(string $cancelledBy, string $reason): VendorOnboardingResult
    {
        if ($this->isTerminalState()) {
            throw new \RuntimeException("Cannot cancel workflow in terminal state: {$this->currentState}");
        }

        $this->transitionTo(self::STATE_CANCELLED, "Cancelled by {$cancelledBy}: {$reason}");

        return VendorOnboardingResult::rejected(
            workflowId: $this->workflowId,
            rejectionReason: "Cancelled: {$reason}",
            rejectedBy: $cancelledBy,
        );
    }

    /**
     * Get workflow state history.
     *
     * @return array<array{from: string, to: string, timestamp: string, reason: ?string}>
     */
    public function getStateHistory(): array
    {
        return $this->stateHistory;
    }

    /**
     * Get validation errors.
     *
     * @return array<VendorValidationError>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Get compliance results.
     *
     * @return array<string, mixed>
     */
    public function getComplianceResults(): array
    {
        return $this->complianceResults;
    }

    /**
     * Check if workflow is in terminal state.
     */
    public function isTerminalState(): bool
    {
        return in_array($this->currentState, [
            self::STATE_ACTIVATED,
            self::STATE_REJECTED,
            self::STATE_CANCELLED,
        ], true);
    }

    /**
     * Check if workflow is complete (successfully).
     */
    public function isComplete(): bool
    {
        return $this->currentState === self::STATE_ACTIVATED;
    }

    /**
     * Export workflow state for persistence.
     *
     * @return array<string, mixed>
     */
    public function exportState(): array
    {
        return [
            'workflow_id' => $this->workflowId,
            'current_state' => $this->currentState,
            'state_history' => $this->stateHistory,
            'validation_errors' => array_map(
                fn (VendorValidationError $e) => $e->toArray(),
                $this->validationErrors,
            ),
            'compliance_results' => $this->complianceResults,
            'document_status' => $this->documentStatus,
            'assigned_approver' => $this->assignedApprover,
            'determined_tier' => $this->determinedTier?->value,
            'request' => $this->request->toArray(),
        ];
    }

    /**
     * Restore workflow from persisted state.
     *
     * @param array<string, mixed> $state
     */
    public function restoreState(array $state): void
    {
        $this->workflowId = $state['workflow_id'];
        $this->currentState = $state['current_state'];
        $this->stateHistory = $state['state_history'] ?? [];
        $this->complianceResults = $state['compliance_results'] ?? [];
        $this->documentStatus = $state['document_status'] ?? [];
        $this->assignedApprover = $state['assigned_approver'];
        $this->determinedTier = isset($state['determined_tier'])
            ? VendorPortalTier::from($state['determined_tier'])
            : null;

        // Restore validation errors
        if (isset($state['validation_errors'])) {
            $this->validationErrors = array_map(
                fn (array $e) => new VendorValidationError(
                    field: $e['field'],
                    code: $e['code'],
                    message: $e['message'],
                    severity: $e['severity'],
                    context: $e['context'] ?? [],
                ),
                $state['validation_errors'],
            );
        }
    }

    // ========================================
    // Private Implementation Methods
    // ========================================

    private function transitionTo(string $newState, ?string $reason = null): void
    {
        $allowedTransitions = self::STATE_TRANSITIONS[$this->currentState] ?? [];

        if (! in_array($newState, $allowedTransitions, true)) {
            throw new \InvalidArgumentException(
                "Invalid state transition from {$this->currentState} to {$newState}",
            );
        }

        $this->stateHistory[] = [
            'from' => $this->currentState,
            'to' => $newState,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'reason' => $reason,
        ];

        $this->logger->debug('Workflow state transition', [
            'workflow_id' => $this->workflowId,
            'from' => $this->currentState,
            'to' => $newState,
            'reason' => $reason,
        ]);

        $this->currentState = $newState;
    }

    private function ensureInState(string $expectedState): void
    {
        if ($this->currentState !== $expectedState) {
            throw new \RuntimeException(
                "Expected workflow state {$expectedState}, but currently in {$this->currentState}",
            );
        }
    }

    private function validateRequiredFields(): void
    {
        if (empty($this->request->vendorName)) {
            $this->validationErrors[] = VendorValidationError::required('vendor_name', 'Vendor name is required');
        }

        if (empty($this->request->taxId)) {
            $this->validationErrors[] = VendorValidationError::required('tax_id', 'Tax ID is required');
        }

        if (empty($this->request->primaryContact['email'] ?? '')) {
            $this->validationErrors[] = VendorValidationError::required('primary_contact.email', 'Primary contact email is required');
        }
    }

    private function validateFormats(): void
    {
        // Validate email format
        $email = $this->request->primaryContact['email'] ?? '';
        if (! empty($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->validationErrors[] = VendorValidationError::invalidFormat(
                'primary_contact.email',
                'Invalid email format',
            );
        }

        // Validate country code
        if (! empty($this->request->countryCode) && strlen($this->request->countryCode) !== 2) {
            $this->validationErrors[] = VendorValidationError::invalidCountry(
                $this->request->countryCode,
                'Country code must be 2-letter ISO code',
            );
        }
    }

    private function checkDuplicates(): void
    {
        // In a real implementation, this would check against database
        // For now, we simulate the check
        $isDuplicate = false; // Would be actual check result

        if ($isDuplicate) {
            $this->validationErrors[] = VendorValidationError::duplicate(
                'tax_id',
                'A vendor with this Tax ID already exists',
            );
        }
    }

    private function hasValidationErrors(): bool
    {
        // Only consider blocking errors
        foreach ($this->validationErrors as $error) {
            if ($error->severity === 'error') {
                return true;
            }
        }

        return false;
    }

    private function handleValidationFailure(): VendorOnboardingResult
    {
        $this->transitionTo(self::STATE_REJECTED, 'Validation failed');

        return VendorOnboardingResult::validationFailed(
            workflowId: $this->workflowId,
            errors: $this->validationErrors,
        );
    }

    /**
     * @return array{matched: bool, lists: array<string>, confidence: float}
     */
    private function performSanctionsScreening(): array
    {
        // In a real implementation, this would call an external sanctions service
        // For now, we simulate a clean result
        return [
            'matched' => false,
            'lists' => [],
            'confidence' => 0.0,
        ];
    }

    /**
     * @param array{matched: bool, lists: array<string>, confidence: float} $result
     */
    private function handleSanctionsMatch(array $result): VendorOnboardingResult
    {
        $this->transitionTo(self::STATE_REJECTED, 'Sanctions match found');

        $this->dispatchEvent(VendorOnboardingRejectedEvent::sanctionMatch(
            tenantId: $this->request->tenantId,
            vendorName: $this->request->vendorName,
            matchedList: implode(', ', $result['lists']),
        ));

        return VendorOnboardingResult::rejected(
            workflowId: $this->workflowId,
            rejectionReason: 'Sanctions screening match',
            rejectedBy: 'SYSTEM',
        );
    }

    /**
     * @return array{risk_level: string, findings: array<string>}
     */
    private function performDueDiligence(): array
    {
        // In a real implementation, this would perform due diligence checks
        return [
            'risk_level' => 'low',
            'findings' => [],
        ];
    }

    private function initializeDocumentRequirements(): void
    {
        // Set up document requirements based on vendor type and country
        $this->documentStatus = [
            'tax_certificate' => ['required' => true, 'received' => false, 'verified' => false],
            'business_registration' => ['required' => true, 'received' => false, 'verified' => false],
            'bank_letter' => ['required' => false, 'received' => false, 'verified' => false],
        ];

        // Foreign vendors need additional documents
        if ($this->request->countryCode !== 'MY') {
            $this->documentStatus['w8_form'] = ['required' => true, 'received' => false, 'verified' => false];
        }

        // Enterprise vendors need more documentation
        if ($this->request->vendorType === 'enterprise') {
            $this->documentStatus['insurance_certificate'] = ['required' => true, 'received' => false, 'verified' => false];
            $this->documentStatus['audited_financials'] = ['required' => true, 'received' => false, 'verified' => false];
        }
    }

    private function hasOutstandingDocuments(): bool
    {
        foreach ($this->documentStatus as $status) {
            if ($status['required'] && ! $status['received']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string>
     */
    private function getOutstandingDocumentsList(): array
    {
        $outstanding = [];
        foreach ($this->documentStatus as $documentType => $status) {
            if ($status['required'] && ! $status['received']) {
                $outstanding[] = $documentType;
            }
        }

        return $outstanding;
    }

    private function routeForApproval(): VendorOnboardingResult
    {
        // Determine tier based on vendor profile
        $this->determinedTier = $this->determineTier();

        // Check if manual approval is required
        $requiresApproval = $this->requiresManualApproval();

        if ($requiresApproval) {
            $this->transitionTo(self::STATE_PENDING_APPROVAL, 'Manual approval required');

            return VendorOnboardingResult::pendingApproval(
                workflowId: $this->workflowId,
                assignedApprover: $this->assignedApprover,
            );
        }

        // Auto-approve
        $this->transitionTo(self::STATE_APPROVED, 'Auto-approved');

        $this->dispatchEvent(VendorOnboardingApprovedEvent::create(
            tenantId: $this->request->tenantId,
            vendorId: $this->workflowId,
            vendorName: $this->request->vendorName,
            approvedBy: 'SYSTEM',
            assignedTier: $this->determinedTier,
        ));

        return $this->activate('SYSTEM');
    }

    private function determineTier(): VendorPortalTier
    {
        // Enterprise vendors get enterprise tier
        if ($this->request->vendorType === 'enterprise') {
            return VendorPortalTier::ENTERPRISE;
        }

        // Foreign vendors with high expected volume get premium
        if ($this->request->countryCode !== 'MY') {
            return VendorPortalTier::PREMIUM;
        }

        // Default to basic
        return VendorPortalTier::BASIC;
    }

    private function requiresManualApproval(): bool
    {
        // Enterprise vendors require approval
        if ($this->determinedTier === VendorPortalTier::ENTERPRISE) {
            return true;
        }

        // Foreign vendors from certain countries require approval
        $highRiskCountries = ['IR', 'KP', 'SY', 'CU'];
        if (in_array($this->request->countryCode, $highRiskCountries, true)) {
            return true;
        }

        // Due diligence found issues
        if (($this->complianceResults['due_diligence']['risk_level'] ?? '') === 'high') {
            return true;
        }

        return false;
    }

    private function handleManualRejection(string $rejectedBy, string $reason): VendorOnboardingResult
    {
        $this->transitionTo(self::STATE_REJECTED, "Rejected by {$rejectedBy}: {$reason}");

        $this->dispatchEvent(VendorOnboardingRejectedEvent::manualRejection(
            tenantId: $this->request->tenantId,
            vendorName: $this->request->vendorName,
            rejectedBy: $rejectedBy,
            reason: $reason,
        ));

        return VendorOnboardingResult::rejected(
            workflowId: $this->workflowId,
            rejectionReason: $reason,
            rejectedBy: $rejectedBy,
        );
    }

    private function generateWorkflowId(): string
    {
        return sprintf('vndr_wf_%s_%s', date('YmdHis'), bin2hex(random_bytes(6)));
    }

    private function generateVendorId(): string
    {
        return sprintf('VND-%s-%s', strtoupper(substr($this->request->countryCode, 0, 2)), strtoupper(bin2hex(random_bytes(6))));
    }

    private function dispatchEvent(object $event): void
    {
        $this->eventDispatcher?->dispatch($event);
    }
}
