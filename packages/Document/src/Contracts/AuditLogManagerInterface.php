<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

interface AuditLogManagerInterface
{
    /**
     * Log an audit entry using a typed payload.
     *
     * @param AuditLogPayloadInterface $payload
     */
    public function log(AuditLogPayloadInterface $payload): void;
}
