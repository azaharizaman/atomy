<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

use Nexus\ProcurementOperations\DTOs\SODValidationRequest;
use Nexus\ProcurementOperations\DTOs\SODValidationResult;
use Nexus\ProcurementOperations\DTOs\SODViolation;
use Nexus\ProcurementOperations\Enums\SODConflictType;

/**
 * Rule that validates receiver cannot process payment for received goods.
 */
final readonly class ReceiverPayerSODRule
{
    /**
     * Validate that payer is not the same as receiver.
     */
    public function check(SODValidationRequest $request): SODValidationResult
    {
        // Only applies to payment processing
        if ($request->action !== 'process_payment') {
            return SODValidationResult::pass($request->userId, $request->action);
        }

        // Check if receiver ID is in metadata
        $receiverId = $request->metadata['receiver_id'] ?? null;
        if ($receiverId === null) {
            return SODValidationResult::pass($request->userId, $request->action);
        }

        // Same user cannot receive and pay
        if ($request->userId === $receiverId) {
            $violation = SODViolation::actionConflict(
                conflictType: SODConflictType::RECEIVER_PAYER,
                userId: $request->userId,
                previousActorId: $receiverId,
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
