<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Contracts;

use Nexus\SourcingOperations\DTOs\ApplyRfqBulkActionCommand;
use Nexus\SourcingOperations\DTOs\DuplicateRfqCommand;
use Nexus\SourcingOperations\DTOs\RemindRfqInvitationCommand;
use Nexus\SourcingOperations\DTOs\RfqLifecycleOutcome;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;
use Nexus\SourcingOperations\DTOs\SaveRfqDraftCommand;
use Nexus\SourcingOperations\DTOs\TransitionRfqStatusCommand;

interface RfqLifecycleCoordinatorInterface
{
    public function duplicate(DuplicateRfqCommand $command): RfqLifecycleOutcome;

    public function saveDraft(SaveRfqDraftCommand $command): RfqLifecycleOutcome;

    /**
     * @param array<int, RfqLifecycleRecord>|null $records Optional pre-loaded records to avoid redundant queries.
     */
    public function applyBulkAction(ApplyRfqBulkActionCommand $command, ?array $records = null): RfqLifecycleOutcome;

    public function transitionStatus(TransitionRfqStatusCommand $command): RfqLifecycleOutcome;

    public function remindInvitation(RemindRfqInvitationCommand $command): RfqLifecycleOutcome;
}
