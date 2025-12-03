<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Recruitment;

use Nexus\Recruitment\Contracts\InterviewSchedulerInterface;

final readonly class ScheduleInterviewHandler
{
    public function __construct(
        private InterviewSchedulerInterface $interviewScheduler
    ) {}
    
    public function handle(string $applicationId, array $scheduleData): void
    {
        // Schedule interview for candidate
        throw new \RuntimeException('Implementation pending');
    }
}
