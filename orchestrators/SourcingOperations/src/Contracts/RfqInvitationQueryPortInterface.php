<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Contracts;

use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;

interface RfqInvitationQueryPortInterface
{
    public function findInvitationByTenantAndId(string $tenantId, string $rfqId, string $invitationId): ?RfqInvitationRecord;
}
