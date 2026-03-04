<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\Document\Contracts\AuditLogManagerInterface;
use Nexus\Document\Contracts\AuditLogPayloadInterface;
use Nexus\AuditLogger\Services\AuditLogManager;

/**
 * Adapter for Document package to use the core AuditLogger.
 */
final readonly class DocumentAuditLogAdapter implements AuditLogManagerInterface
{
    public function __construct(
        private AuditLogManager $manager
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function log(AuditLogPayloadInterface $payload): void
    {
        $this->manager->log(
            logName: $payload->getLogName(),
            description: $payload->getDescription(),
            subjectType: $payload->getSubjectType(),
            subjectId: $payload->getSubjectId(),
            causerType: $payload->getCauserType(),
            causerId: $payload->getCauserId(),
            properties: $payload->getProperties(),
            level: $payload->getLevel()
        );
    }
}
