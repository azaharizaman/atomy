<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Recruitment;

use Nexus\Recruitment\Contracts\InterviewEvaluatorInterface;

final readonly class EvaluateInterviewHandler
{
    public function __construct(
        private InterviewEvaluatorInterface $interviewEvaluator
    ) {}
    
    public function handle(string $interviewId, array $evaluationData): void
    {
        // Evaluate interview performance
        throw new \RuntimeException('Implementation pending');
    }
}
