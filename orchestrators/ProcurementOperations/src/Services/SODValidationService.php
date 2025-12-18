<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DataProviders\SODComplianceDataProvider;
use Nexus\ProcurementOperations\DTOs\SODValidationRequest;
use Nexus\ProcurementOperations\DTOs\SODValidationResult;
use Nexus\ProcurementOperations\DTOs\SODViolation;
use Nexus\ProcurementOperations\Enums\SODConflictType;
use Nexus\ProcurementOperations\Events\SODValidationPassedEvent;
use Nexus\ProcurementOperations\Events\SODViolationDetectedEvent;
use Nexus\ProcurementOperations\Rules\POCreatorInvoiceMatcherSODRule;
use Nexus\ProcurementOperations\Rules\ReceiverPayerSODRule;
use Nexus\ProcurementOperations\Rules\RequestorApproverSODRule;
use Nexus\ProcurementOperations\Rules\VendorCreatorPayerSODRule;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for validating Segregation of Duties compliance.
 *
 * Checks that users don't perform conflicting actions that
 * could enable fraud or policy violations.
 */
final readonly class SODValidationService
{
    private RequestorApproverSODRule $requestorApproverRule;
    private ReceiverPayerSODRule $receiverPayerRule;
    private VendorCreatorPayerSODRule $vendorCreatorPayerRule;
    private POCreatorInvoiceMatcherSODRule $poCreatorMatcherRule;

    public function __construct(
        private SODComplianceDataProvider $dataProvider,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
        private bool $blockOnHighRisk = true,
        private ?SecureIdGeneratorInterface $idGenerator = null,
    ) {
        $this->requestorApproverRule = new RequestorApproverSODRule();
        $this->receiverPayerRule = new ReceiverPayerSODRule();
        $this->vendorCreatorPayerRule = new VendorCreatorPayerSODRule();
        $this->poCreatorMatcherRule = new POCreatorInvoiceMatcherSODRule();
    }

    /**
     * Validate SOD compliance for a request.
     *
     * @throws SODViolationException If blocking is enabled and HIGH risk violation found
     */
    public function validate(SODValidationRequest $request): SODValidationResult
    {
        $violations = [];
        $checksPerformed = 0;

        // Enrich request with user roles if not provided
        $userRoles = !empty($request->userRoles)
            ? $request->userRoles
            : $this->dataProvider->getUserRoles($request->userId);

        $enrichedRequest = new SODValidationRequest(
            userId: $request->userId,
            action: $request->action,
            entityType: $request->entityType,
            entityId: $request->entityId,
            userRoles: $userRoles,
            conflictsToCheck: $request->conflictsToCheck,
            metadata: $this->enrichMetadata($request),
        );

        // Get conflicts to check
        $conflictsToCheck = $request->conflictsToCheck ?? SODConflictType::cases();

        // Run applicable rules
        foreach ($conflictsToCheck as $conflictType) {
            $checksPerformed++;
            $ruleResult = $this->runRule($conflictType, $enrichedRequest);

            if (!$ruleResult->passed) {
                $violations = array_merge($violations, $ruleResult->violations);
            }
        }

        // Build result
        $result = empty($violations)
            ? SODValidationResult::pass($request->userId, $request->action)
            : SODValidationResult::fail($request->userId, $request->action, $violations);

        // Dispatch events
        if ($result->passed) {
            $this->eventDispatcher->dispatch(new SODValidationPassedEvent(
                userId: $request->userId,
                action: $request->action,
                entityType: $request->entityType,
                entityId: $request->entityId,
                checksPerformed: $checksPerformed,
                validatedAt: new \DateTimeImmutable(),
            ));
        } else {
            foreach ($violations as $violation) {
                $this->dispatchViolationEvent($violation, $request);
            }
        }

        // Block if HIGH risk and blocking enabled
        if ($this->blockOnHighRisk && $result->hasHighRiskViolations()) {
            $this->logger->warning('SOD HIGH risk violation blocked', [
                'user_id' => $request->userId,
                'action' => $request->action,
                'violations' => count($violations),
            ]);

            throw new SODViolationException(
                'Action blocked due to Segregation of Duties violation',
                $result
            );
        }

        return $result;
    }

    /**
     * Check if a specific action would violate SOD.
     *
     * @return bool True if action is allowed, false if it would violate SOD
     */
    public function isActionAllowed(
        string $userId,
        string $action,
        string $entityType,
        string $entityId,
        array $metadata = [],
    ): bool {
        $request = new SODValidationRequest(
            userId: $userId,
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            metadata: $metadata,
        );

        try {
            $result = $this->validate($request);

            return $result->passed;
        } catch (SODViolationException) {
            return false;
        }
    }

    /**
     * Get all potential SOD conflicts for a user based on their roles.
     *
     * @return array<SODConflictType>
     */
    public function getPotentialConflicts(string $userId): array
    {
        $userRoles = $this->dataProvider->getUserRoles($userId);
        $conflicts = [];

        foreach (SODConflictType::cases() as $conflictType) {
            $conflictingRoles = $conflictType->getConflictingRoles();

            // Check if user has both conflicting roles
            if (
                in_array($conflictingRoles[0], $userRoles, true) &&
                in_array($conflictingRoles[1], $userRoles, true)
            ) {
                $conflicts[] = $conflictType;
            }
        }

        return $conflicts;
    }

    /**
     * Run a specific SOD rule.
     */
    private function runRule(SODConflictType $conflictType, SODValidationRequest $request): SODValidationResult
    {
        return match ($conflictType) {
            SODConflictType::REQUESTOR_APPROVER => $this->requestorApproverRule->check($request),
            SODConflictType::RECEIVER_PAYER => $this->receiverPayerRule->check($request),
            SODConflictType::VENDOR_CREATOR_PAYER => $this->vendorCreatorPayerRule->check($request),
            SODConflictType::PO_CREATOR_INVOICE_MATCHER => $this->poCreatorMatcherRule->check($request),
            // Add more rules as needed
            default => SODValidationResult::pass($request->userId, $request->action),
        };
    }

    /**
     * Enrich request metadata with entity context.
     *
     * @return array<string, mixed>
     */
    private function enrichMetadata(SODValidationRequest $request): array
    {
        $metadata = $request->metadata;

        // Add context from data provider if not already present
        $context = $this->dataProvider->getValidationContext(
            $request->userId,
            $request->entityType,
            $request->entityId
        );

        return array_merge($context, $metadata);
    }

    /**
     * Dispatch violation event.
     */
    private function dispatchViolationEvent(SODViolation $violation, SODValidationRequest $request): void
    {
        $violationId = $this->idGenerator !== null
            ? $this->idGenerator->generateHex(16)
            : bin2hex(random_bytes(16));

        $this->eventDispatcher->dispatch(new SODViolationDetectedEvent(
            violationId: $violationId,
            userId: $violation->userId,
            conflictingUserId: $violation->conflictingUserId,
            conflictType: $violation->conflictType,
            entityType: $violation->entityType,
            entityId: $violation->entityId,
            action: $request->action,
            riskLevel: $violation->conflictType->getRiskLevel(),
            blocked: $this->blockOnHighRisk && $violation->conflictType->getRiskLevel() === 'HIGH',
            detectedAt: new \DateTimeImmutable(),
            metadata: $request->metadata,
        ));

        $this->logger->info('SOD violation detected', [
            'user_id' => $violation->userId,
            'conflict_type' => $violation->conflictType->value,
            'risk_level' => $violation->conflictType->getRiskLevel(),
            'entity_type' => $violation->entityType,
            'entity_id' => $violation->entityId,
        ]);
    }
}

/**
 * Exception thrown when SOD violation blocks an action.
 */
class SODViolationException extends \Exception
{
    public function __construct(
        string $message,
        public readonly SODValidationResult $validationResult,
    ) {
        parent::__construct($message);
    }
}
