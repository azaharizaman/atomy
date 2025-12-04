<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

use Nexus\HumanResourceOperations\DTOs\LeaveApplicationRequest;
use Nexus\HumanResourceOperations\DTOs\LeaveContext;

/**
 * Service for leave balance calculations and management.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Services perform calculations, not Coordinators
 * - Stateless business logic
 */
final readonly class LeaveBalanceService
{
    /**
     * Create leave request and calculate new balance.
     * 
     * @return array{leaveRequestId: string, newBalance: float}
     */
    public function createLeaveRequest(
        LeaveApplicationRequest $request,
        LeaveContext $context,
        float $daysRequested
    ): array {
        // Generate secure UUID for leave request
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $leaveRequestId = 'leave-' . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        
        // Calculate new balance
        $newBalance = $context->currentBalance - $daysRequested;
        
        // Implementation: Call Nexus\Leave package to persist the leave request
        // This would interact with LeaveManagerInterface from the atomic package
        
        return [
            'leaveRequestId' => $leaveRequestId,
            'newBalance' => $newBalance,
        ];
    }

    /**
     * Calculate remaining balance after leave request.
     */
    public function calculateRemainingBalance(
        float $currentBalance,
        float $daysRequested
    ): float {
        return $currentBalance - $daysRequested;
    }
}
