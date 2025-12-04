<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Unit\Rules;

use Nexus\HumanResourceOperations\DTOs\ApplicationContext;
use Nexus\HumanResourceOperations\Rules\MeetsMinimumQualificationsRule;
use PHPUnit\Framework\TestCase;

final class MeetsMinimumQualificationsRuleTest extends TestCase
{
    private MeetsMinimumQualificationsRule $rule;

    protected function setUp(): void
    {
        $this->rule = new MeetsMinimumQualificationsRule();
    }

    public function test_passes_when_candidate_meets_qualifications(): void
    {
        $context = new ApplicationContext(
            applicationId: 'app-1',
            jobPostingId: 'job-1',
            candidateName: 'John Doe',
            qualifications: [
                'education' => 'Bachelors in Computer Science',
                'experience_years' => 5,
                'skills' => ['PHP', 'Laravel', 'MySQL']
            ]
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('qualifications verified', $result->message);
    }

    public function test_fails_when_qualifications_missing(): void
    {
        $context = new ApplicationContext(
            applicationId: 'app-1',
            jobPostingId: 'job-1',
            candidateName: 'John Doe',
            qualifications: []
        );

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('No qualifications', $result->message);
    }

    public function test_returns_correct_rule_name(): void
    {
        $this->assertEquals('Meets Minimum Qualifications Rule', $this->rule->getName());
    }
}
