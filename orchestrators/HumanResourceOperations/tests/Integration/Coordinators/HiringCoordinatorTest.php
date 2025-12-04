<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Integration\Coordinators;

use Nexus\HumanResourceOperations\Coordinators\HiringCoordinator;
use Nexus\HumanResourceOperations\DataProviders\RecruitmentDataProvider;
use Nexus\HumanResourceOperations\DTOs\ApplicationContext;
use Nexus\HumanResourceOperations\DTOs\HiringRequest;
use Nexus\HumanResourceOperations\Rules\AllInterviewsCompletedRule;
use Nexus\HumanResourceOperations\Rules\MeetsMinimumQualificationsRule;
use Nexus\HumanResourceOperations\Services\EmployeeRegistrationService;
use Nexus\HumanResourceOperations\Services\HiringRuleRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HiringCoordinatorTest extends TestCase
{
    private HiringCoordinator $coordinator;
    private MockObject $dataProvider;
    private MockObject $registrationService;

    protected function setUp(): void
    {
        // Mock DataProvider
        $this->dataProvider = $this->createMock(RecruitmentDataProvider::class);
        
        // Mock RegistrationService
        $this->registrationService = $this->createMock(EmployeeRegistrationService::class);
        
        // Create real RuleRegistry with real rules
        $ruleRegistry = new HiringRuleRegistry([
            new AllInterviewsCompletedRule(),
            new MeetsMinimumQualificationsRule()
        ]);

        $this->coordinator = new HiringCoordinator(
            dataProvider: $this->dataProvider,
            ruleRegistry: $ruleRegistry,
            registrationService: $this->registrationService,
            logger: new NullLogger()
        );
    }

    public function test_successfully_processes_hiring_decision_when_all_rules_pass(): void
    {
        // Arrange
        $request = new HiringRequest(
            applicationId: 'app-123',
            jobPostingId: 'job-456',
            hired: true,
            decidedBy: 'manager-789',
            startDate: new \DateTimeImmutable('2024-02-01'),
            positionId: 'pos-001',
            departmentId: 'dept-hr'
        );

        $context = new ApplicationContext(
            applicationId: 'app-123',
            jobPostingId: 'job-456',
            candidateName: 'John Doe',
            qualifications: ['degree' => 'BSc Computer Science'],
            interviewResults: [
                ['stage' => 'technical', 'status' => 'completed', 'score' => 90],
                ['stage' => 'final', 'status' => 'completed', 'score' => 85]
            ]
        );

        $this->dataProvider
            ->expects($this->once())
            ->method('getApplicationContext')
            ->with('app-123')
            ->willReturn($context);

        $this->registrationService
            ->expects($this->once())
            ->method('registerNewEmployee')
            ->willReturn([
                'employeeId' => 'emp-new-123',
                'userId' => 'user-new-456',
                'partyId' => 'party-new-789'
            ]);

        // Act
        $result = $this->coordinator->processHiringDecision($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('app-123', $result->applicationId);
        $this->assertEquals('emp-new-123', $result->employeeId);
        $this->assertStringContainsString('successfully', $result->message);
    }

    public function test_rejects_hiring_when_interviews_not_completed(): void
    {
        // Arrange
        $request = new HiringRequest(
            applicationId: 'app-123',
            jobPostingId: 'job-456',
            hired: true,
            decidedBy: 'manager-789'
        );

        $context = new ApplicationContext(
            applicationId: 'app-123',
            jobPostingId: 'job-456',
            candidateName: 'John Doe',
            qualifications: ['degree' => 'BSc Computer Science'],
            interviewResults: [
                ['stage' => 'technical', 'status' => 'pending', 'score' => null]
            ]
        );

        $this->dataProvider
            ->expects($this->once())
            ->method('getApplicationContext')
            ->with('app-123')
            ->willReturn($context);

        $this->registrationService
            ->expects($this->never())
            ->method('registerNewEmployee');

        // Act
        $result = $this->coordinator->processHiringDecision($request);

        // Assert
        $this->assertFalse($result->success);
        $this->assertNull($result->employeeId);
        $this->assertNotEmpty($result->rejectionReasons);
    }

    public function test_handles_rejection_gracefully(): void
    {
        // Arrange
        $request = new HiringRequest(
            applicationId: 'app-123',
            jobPostingId: 'job-456',
            hired: false,
            decidedBy: 'manager-789'
        );

        $this->registrationService
            ->expects($this->never())
            ->method('registerNewEmployee');

        // Act
        $result = $this->coordinator->processHiringDecision($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertNull($result->employeeId);
        $this->assertStringContainsString('rejected', $result->message);
    }
}
