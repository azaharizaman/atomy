<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

use Nexus\SourcingOperations\Exceptions\CommandValidationException;

final readonly class RemindRfqInvitationCommand
{
    public string $tenantId;
    public string $rfqId;
    public string $invitationId;
    public string $requestedByPrincipalId;

    public function __construct(
        string $tenantId,
        string $rfqId,
        string $invitationId,
        string $requestedByPrincipalId,
    ) {
        if (trim($tenantId) === '') {
            throw new CommandValidationException('Tenant id cannot be empty.');
        }

        if (trim($rfqId) === '') {
            throw new CommandValidationException('RFQ id cannot be empty.');
        }

        if (trim($invitationId) === '') {
            throw new CommandValidationException('Invitation id cannot be empty.');
        }

        if (trim($requestedByPrincipalId) === '') {
            throw new CommandValidationException('Requested-by principal id cannot be empty.');
        }

        $this->tenantId = trim($tenantId);
        $this->rfqId = trim($rfqId);
        $this->invitationId = trim($invitationId);
        $this->requestedByPrincipalId = trim($requestedByPrincipalId);
    }
}
