<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Leave;

use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Leave\Contracts\LeavePolicyInterface;
use Nexus\Leave\Contracts\LeaveRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Apply Leave Use Case Handler.
 */
final readonly class ApplyLeaveHandler
{
    public function __construct(
        private LeaveRepositoryInterface $leaveRepository,
        private LeaveBalanceRepositoryInterface $balanceRepository,
        private LeavePolicyInterface $leavePolicy,
        private LoggerInterface $logger
    ) {}

    /**
     * @param array<string,mixed> $leaveData
     */
    public function handle(string $employeeId, array $leaveData): string
    {
        $this->logger->info('Processing leave application', ['employee_id' => $employeeId]);

        $leaveTypeId = (string) ($leaveData['leaveTypeId'] ?? '');
        $daysRequested = (float) ($leaveData['daysRequested'] ?? 0.0);

        $balance = $this->balanceRepository->findByEmployeeAndType($employeeId, $leaveTypeId);
        $currentBalance = $this->extractBalance($balance);

        if ($currentBalance < $daysRequested) {
            throw new \DomainException('Insufficient leave balance');
        }

        $policyErrors = $this->leavePolicy->validateLeaveRequest($leaveData);
        if ($policyErrors !== []) {
            throw new \DomainException('Leave policy validation failed: ' . implode('; ', $policyErrors));
        }

        $leaveRecord = (object) array_merge($leaveData, [
            'employeeId' => $employeeId,
            'status' => 'pending',
            'createdAt' => new \DateTimeImmutable(),
        ]);

        $leaveId = $this->leaveRepository->save($leaveRecord);

        if ($balance !== null) {
            $balanceId = $this->extractId($balance);
            if ($balanceId !== null) {
                $this->balanceRepository->updateBalance($balanceId, $currentBalance - $daysRequested);
            }
        }

        $this->logger->info('Leave application created', ['employee_id' => $employeeId, 'leave_id' => $leaveId]);

        return $leaveId;
    }

    private function extractBalance(?object $balance): float
    {
        if ($balance === null) {
            return 0.0;
        }

        if (method_exists($balance, 'getBalance')) {
            return (float) $balance->getBalance();
        }

        return (float) ($balance->balance ?? 0.0);
    }

    private function extractId(object $entity): ?string
    {
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
            return is_string($id) ? $id : null;
        }

        return isset($entity->id) ? (string) $entity->id : null;
    }
}
