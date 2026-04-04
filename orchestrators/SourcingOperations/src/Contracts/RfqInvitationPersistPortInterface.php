<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Contracts;

use Nexus\SourcingOperations\DTOs\RemindRfqInvitationCommand;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;

interface RfqInvitationPersistPortInterface
{
    public function markInvitationReminded(RfqInvitationRecord $invitation, RemindRfqInvitationCommand $command): RfqInvitationRecord;
}
