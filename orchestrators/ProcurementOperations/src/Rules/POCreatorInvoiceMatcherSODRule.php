<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

use Nexus\ProcurementOperations\DTOs\SODValidationRequest;
use Nexus\ProcurementOperations\DTOs\SODValidationResult;
use Nexus\ProcurementOperations\DTOs\SODViolation;
use Nexus\ProcurementOperations\Enums\SODConflictType;

/**
 * Rule that validates PO creator cannot match invoices to their own PO.
 */
final readonly class POCreatorInvoiceMatcherSODRule
{
    /**
     * Validate that invoice matcher did not create the PO.
     */
    public function check(SODValidationRequest $request): SODValidationResult
    {
        // Only applies to invoice matching
        if ($request->action !== 'match_invoice') {
            return SODValidationResult::pass($request->userId, $request->action);
        }

        // Check if PO creator ID is in metadata
        $poCreatorId = $request->metadata['po_creator_id'] ?? null;
        if ($poCreatorId === null) {
            return SODValidationResult::pass($request->userId, $request->action);
        }

        // Same user cannot create PO and match invoices
        if ($request->userId === $poCreatorId) {
            $violation = SODViolation::actionConflict(
                conflictType: SODConflictType::PO_CREATOR_INVOICE_MATCHER,
                userId: $request->userId,
                previousActorId: $poCreatorId,
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
