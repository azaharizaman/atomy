<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\DTOs;

/**
 * Escalation Workflow Result DTO
 * 
 * Represents the result of an escalation workflow operation.
 * 
 * @package Nexus\CRMOperations\DTOs
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class EscalationWorkflowResult
{
    /**
     * @param bool $success Whether workflow succeeded
     * @param int $breachesFound Number of breaches found
     * @param int $warningsFound Number of warnings found
     * @param array<array<string, mixed>> $breaches List of breach entries with entity_id, entity_type, and breaches data
     * @param array<array<string, mixed>> $warnings List of warning entries with entity_id, entity_type, and warnings data
     */
    public function __construct(
        public bool $success,
        public int $breachesFound,
        public int $warningsFound,
        public array $breaches,
        public array $warnings
    ) {}
}
