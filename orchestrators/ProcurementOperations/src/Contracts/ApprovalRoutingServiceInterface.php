<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\ApprovalRoutingRequest;
use Nexus\ProcurementOperations\DTOs\ApprovalRoutingResult;

/**
 * Contract for approval routing service.
 *
 * Determines the approval chain for procurement documents
 * based on amount, category, and organizational hierarchy.
 */
interface ApprovalRoutingServiceInterface
{
    /**
     * Determine the approval routing for a document.
     *
     * @param ApprovalRoutingRequest $request Routing request with document details
     * @return ApprovalRoutingResult Routing result with approval chain
     */
    public function determineRouting(ApprovalRoutingRequest $request): ApprovalRoutingResult;
}
