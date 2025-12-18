<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

use Nexus\HumanResourceOperations\Contracts\SecureIdGeneratorInterface;

/**
 * Service for employee registration operations.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Services perform "Heavy Lifting" - complex logic that crosses boundaries
 * - NOT inline in Coordinator
 * - Orchestrates calls to multiple atomic packages (Hrm, Identity, Party, etc.)
 */
final readonly class EmployeeRegistrationService
{
    public function __construct(
        // Dependencies from atomic packages will be injected by consuming application
        // e.g., EmployeeManagerInterface, UserManagerInterface, PartyManagerInterface
        private ?SecureIdGeneratorInterface $idGenerator = null,
    ) {}

    /**
     * Register a new employee after successful hiring.
     * 
     * This crosses multiple package boundaries:
     * - Nexus\Hrm (employee record)
     * - Nexus\Identity (user account)
     * - Nexus\Party (party record)
     * - Nexus\OrgStructure (department assignment)
     * 
     * @return array{employeeId: string, userId: string, partyId: string}
     */
    public function registerNewEmployee(
        string $applicationId,
        string $candidateName,
        string $candidateEmail,
        string $positionId,
        string $departmentId,
        string $startDate,
        ?string $reportsTo = null,
        array $metadata = [],
    ): array {
        // Step 1: Create Party record (Nexus\Party)
        $partyId = $this->createPartyRecord($candidateName, $candidateEmail);

        // Step 2: Create User account (Nexus\Identity)
        $userId = $this->createUserAccount($candidateEmail, $candidateName, $partyId);

        // Step 3: Create Employee record (Nexus\Hrm)
        $employeeId = $this->createEmployeeRecord(
            partyId: $partyId,
            userId: $userId,
            positionId: $positionId,
            departmentId: $departmentId,
            startDate: $startDate,
            reportsTo: $reportsTo,
            metadata: $metadata,
        );

        // Step 4: Assign to organization (Nexus\OrgStructure)
        $this->assignToOrganization($employeeId, $departmentId);

        return [
            'employeeId' => $employeeId,
            'userId' => $userId,
            'partyId' => $partyId,
        ];
    }

    private function createPartyRecord(string $name, string $email): string
    {
        // Implementation: Call Nexus\Party package
        // Using cryptographically secure UUID via SecureIdGenerator
        if ($this->idGenerator !== null) {
            return 'party-' . $this->idGenerator->generateUuid();
        }

        // Fallback for backward compatibility
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return 'party-' . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function createUserAccount(string $email, string $name, string $partyId): string
    {
        // Implementation: Call Nexus\Identity package
        // Using cryptographically secure UUID via SecureIdGenerator
        if ($this->idGenerator !== null) {
            return 'user-' . $this->idGenerator->generateUuid();
        }

        // Fallback for backward compatibility
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return 'user-' . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function createEmployeeRecord(
        string $partyId,
        string $userId,
        string $positionId,
        string $departmentId,
        string $startDate,
        ?string $reportsTo,
        array $metadata,
    ): string {
        // Implementation: Call Nexus\Hrm package
        // Using cryptographically secure UUID via SecureIdGenerator
        if ($this->idGenerator !== null) {
            return 'emp-' . $this->idGenerator->generateUuid();
        }

        // Fallback for backward compatibility
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return 'emp-' . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function assignToOrganization(string $employeeId, string $departmentId): void
    {
        // Implementation: Call Nexus\OrgStructure package
    }
}
