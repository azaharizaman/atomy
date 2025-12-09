<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

use Nexus\ProcurementOperations\DTOs\SODValidationRequest;
use Nexus\ProcurementOperations\DTOs\SODValidationResult;
use Nexus\ProcurementOperations\DTOs\SODViolation;
use Nexus\ProcurementOperations\Enums\SODConflictType;

/**
 * Rule that validates requestor cannot approve their own requisition.
 */
final readonly class RequestorApproverSODRule
{
    /**
     * Validate that approver is not the same as requestor.
     */
    public function check(SODValidationRequest $request): SODValidationResult
    {
        // Only applies to approval actions
        if (!in_array($request->action, ['approve_requisition', 'approve_po'], true)) {
            return SODValidationResult::pass($request->userId, $request->action);
        }

        // Check if requestor ID is in metadata
        $requestorId = $request->metadata['requestor_id'] ?? null;
        if ($requestorId === null) {
            return SODValidationResult::pass($request->userId, $request->action);
        }

        // Same user cannot request and approve
        if ($request->userId === $requestorId) {
            $violation = SODViolation::actionConflict(
                conflictType: SODConflictType::REQUESTOR_APPROVER,
                userId: $request->userId,
                previousActorId: $requestorId,
                entityType: $request->entityType,
                entityId: $request->entityId,
            );

            return SODValidationResult::fail(
                $request->userId,
                $request->action,
                [$violation]
            );
        }

        return SODValidationResult::pass($request->userId, $request->action);
    }
}
