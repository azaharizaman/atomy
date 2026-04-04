<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Contracts;

use Nexus\Sourcing\ValueObjects\RfqBulkAction;
use Nexus\SourcingOperations\DTOs\DuplicateRfqCommand;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;
use Nexus\SourcingOperations\DTOs\RfqLineItemRecord;
use Nexus\SourcingOperations\DTOs\SaveRfqDraftCommand;
use Nexus\SourcingOperations\DTOs\TransitionRfqStatusCommand;

interface RfqLifecyclePersistPortInterface
{
    /**
     * @param array<int, RfqLineItemRecord> $lineItems
     */
    public function createDuplicate(RfqLifecycleRecord $sourceRfq, DuplicateRfqCommand $command, array $lineItems): RfqLifecycleRecord;

    public function saveDraft(RfqLifecycleRecord $rfq, SaveRfqDraftCommand $command): RfqLifecycleRecord;

    public function transitionStatus(RfqLifecycleRecord $rfq, TransitionRfqStatusCommand $command): RfqLifecycleRecord;

    /**
     * @param array<int, string> $rfqIds
     */
    public function applyBulkAction(string $tenantId, RfqBulkAction $action, array $rfqIds): int;
}
