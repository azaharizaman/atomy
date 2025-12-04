<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Unit\Rules;

use Nexus\HumanResourceOperations\DTOs\ApplicationContext;
use Nexus\HumanResourceOperations\Rules\AllInterviewsCompletedRule;
use PHPUnit\Framework\TestCase;

final class AllInterviewsCompletedRuleTest extends TestCase
{
    private AllInterviewsCompletedRule $rule;

    protected function setUp(): void
    {
        $this->rule = new AllInterviewsCompletedRule();
    }

    public function test_passes_when_all_interviews_completed(): void
    {
        $context = new ApplicationContext(
            applicationId: 'app-1',
            jobPostingId: 'job-1',
            candidateName: 'John Doe',
            interviewResults: [
                ['stage' => 'phone_screening', 'status' => 'completed', 'score' => 85],
                ['stage' => 'technical', 'status' => 'completed', 'score' => 90],
                ['stage' => 'final', 'status' => 'completed', 'score' => 88],
            ]
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertEquals('All interviews completed successfully', $result->message);
        $this->assertEquals('All Interviews Completed Rule', $result->ruleName);
    }

    public function test_fails_when_interviews_not_completed(): void
    {
        $context = new ApplicationContext(
            applicationId: 'app-1',
            jobPostingId: 'job-1',
            candidateName: 'John Doe',
            interviewResults: [
                ['stage' => 'phone_screening', 'status' => 'completed', 'score' => 85],
                ['stage' => 'technical', 'status' => 'pending', 'score' => null],
                ['stage' => 'final', 'status' => 'not_started', 'score' => null],
            ]
        );

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not completed', $result->message);
        $this->assertArrayHasKey('incomplete_stages', $result->metadata);
        $this->assertEquals(['technical', 'final'], $result->metadata['incomplete_stages']);
    }

    public function test_fails_when_no_interviews_exist(): void
    {
        $context = new ApplicationContext(
            applicationId: 'app-1',
            jobPostingId: 'job-1',
            candidateName: 'John Doe',
            interviewResults: []
        );

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('No interview results', $result->message);
    }

    public function test_returns_correct_rule_name(): void
    {
        $this->assertEquals('All Interviews Completed Rule', $this->rule->getName());
    }
}
