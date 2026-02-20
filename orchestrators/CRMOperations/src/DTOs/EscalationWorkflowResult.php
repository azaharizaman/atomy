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
     * @param array<string, mixed> $breaches List of breaches
     * @param array<string, mixed> $warnings List of warnings
     */
    public function __construct(
        public bool $success,
        public int $breachesFound,
        public int $warningsFound,
        public array $breaches,
        public array $warnings
    ) {}
}
