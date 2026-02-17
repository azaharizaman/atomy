<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Leave;

use Nexus\Leave\Contracts\LeaveRepositoryInterface;

final readonly class ApproveLeaveHandler
{
    public function __construct(
        private LeaveRepositoryInterface $leaveRepository
    ) {}

    public function handle(string $leaveId, string $approvedBy): object
    {
        $leave = $this->leaveRepository->findById($leaveId);
        if ($leave === null) {
            throw new \RuntimeException('Leave request not found: ' . $leaveId);
        }

        $leave->status = 'approved';
        $leave->approvedBy = $approvedBy;
        $leave->approvedAt = new \DateTimeImmutable();

        $this->leaveRepository->save($leave);

        return $leave;
    }
}
