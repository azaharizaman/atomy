<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

use Nexus\ProcurementOperations\DTOs\SODValidationRequest;
use Nexus\ProcurementOperations\DTOs\SODValidationResult;
use Nexus\ProcurementOperations\DTOs\SODViolation;
use Nexus\ProcurementOperations\Enums\SODConflictType;

/**
 * Rule that validates vendor creator cannot process payment.
 * Prevents fictitious vendor fraud.
 */
final readonly class VendorCreatorPayerSODRule
{
    /**
     * Validate that payer did not create the vendor.
     */
    public function check(SODValidationRequest $request): SODValidationResult
    {
        // Only applies to payment processing
        if ($request->action !== 'process_payment') {
            return SODValidationResult::pass($request->userId, $request->action);
        }

        // Check if vendor creator ID is in metadata
        $vendorCreatorId = $request->metadata['vendor_creator_id'] ?? null;
        if ($vendorCreatorId === null) {
            return SODValidationResult::pass($request->userId, $request->action);
        }

        // Same user cannot create vendor and process payment
        if ($request->userId === $vendorCreatorId) {
            $violation = SODViolation::actionConflict(
                conflictType: SODConflictType::VENDOR_CREATOR_PAYER,
                userId: $request->userId,
                previousActorId: $vendorCreatorId,
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
