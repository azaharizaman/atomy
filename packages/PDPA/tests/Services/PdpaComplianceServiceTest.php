<?php

declare(strict_types=1);

namespace Nexus\PDPA\Tests\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestManagerInterface;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\PDPA\Exceptions\PdpaException;
use Nexus\PDPA\Services\PdpaComplianceService;
use Nexus\PDPA\ValueObjects\PdpaDeadline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdpaComplianceService::class)]
final class PdpaComplianceServiceTest extends TestCase
{
    private DataSubjectRequestManagerInterface&MockObject $requestManager;
    private PdpaComplianceService $service;

    protected function setUp(): void
    {
        $this->requestManager = $this->createMock(DataSubjectRequestManagerInterface::class);
        $this->service = new PdpaComplianceService($this->requestManager);
    }

    #[Test]
    public function it_calculates_deadline_with_21_day_standard(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);

        $deadline = $this->service->calculateDeadline($request);

        $expectedDeadline = $submittedAt->modify('+21 days');
        $this->assertEquals($expectedDeadline, $deadline->getDeadlineDate());
        $this->assertEquals('PDPA 2010', $deadline->getRegulation());
    }

    #[Test]
    public function it_calculates_extended_deadline_from_metadata(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt, metadata: [
            'deadline_extended' => true,
            'extension_reason' => 'Commissioner approval',
            'extension_days' => 10,
        ]);

        $deadline = $this->service->calculateDeadline($request);

        // 21 + 10 = 31 days
        $expectedDeadline = $submittedAt->modify('+31 days');
        $this->assertEquals($expectedDeadline, $deadline->getDeadlineDate());
        $this->assertTrue($deadline->isExtended());
    }

    #[Test]
    public function it_can_extend_non_extended_request(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);

        $this->assertTrue($this->service->canExtendDeadline($request));
    }

    #[Test]
    public function it_cannot_extend_already_extended_request(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt, metadata: [
            'deadline_extended' => true,
        ]);

        $this->assertFalse($this->service->canExtendDeadline($request));
    }

    #[Test]
    public function it_extends_deadline_with_14_day_max(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);

        $extendedDeadline = $this->service->extendDeadline($request, 'Complex verification');

        // 21 + 14 = 35 days
        $expectedDeadline = $submittedAt->modify('+35 days');
        $this->assertEquals($expectedDeadline, $extendedDeadline->getDeadlineDate());
        $this->assertTrue($extendedDeadline->isExtended());
        $this->assertEquals('Complex verification', $extendedDeadline->getExtensionReason());
    }

    #[Test]
    public function it_throws_exception_when_extending_already_extended(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt, metadata: [
            'deadline_extended' => true,
        ]);

        $this->expectException(PdpaException::class);
        $this->service->extendDeadline($request, 'Second extension');
    }

    #[Test]
    public function it_detects_overdue_request(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);

        // Check 25 days later - overdue
        $checkDate = $submittedAt->modify('+25 days');
        $this->assertTrue($this->service->isOverdue($request, $checkDate));
    }

    #[Test]
    public function it_detects_non_overdue_request(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt);

        // Check 10 days later - not overdue
        $checkDate = $submittedAt->modify('+10 days');
        $this->assertFalse($this->service->isOverdue($request, $checkDate));
    }

    #[Test]
    public function completed_request_is_not_overdue(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt, status: RequestStatus::COMPLETED);

        // Even 30 days later, completed is not overdue
        $checkDate = $submittedAt->modify('+30 days');
        $this->assertFalse($this->service->isOverdue($request, $checkDate));
    }

    #[Test]
    public function rejected_request_is_not_overdue(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-15');
        $request = $this->createRequest($submittedAt, status: RequestStatus::REJECTED);

        $checkDate = $submittedAt->modify('+30 days');
        $this->assertFalse($this->service->isOverdue($request, $checkDate));
    }

    #[Test]
    public function it_gets_overdue_requests(): void
    {
        $now = new DateTimeImmutable();
        $overdueRequest = $this->createRequest($now->modify('-25 days'));
        $onTimeRequest = $this->createRequest($now->modify('-5 days'));

        $this->requestManager->method('getActiveRequests')
            ->willReturn([$overdueRequest, $onTimeRequest]);

        $overdueRequests = $this->service->getOverdueRequests();

        $this->assertCount(1, $overdueRequests);
        $this->assertSame($overdueRequest, $overdueRequests[0]);
    }

    #[Test]
    public function it_validates_pdpa_compliance_for_overdue_request(): void
    {
        // Create a request submitted 30 days ago - well past the 21-day deadline
        $submittedAt = new DateTimeImmutable('-30 days');
        $request = $this->createRequest($submittedAt);

        // Request is already overdue based on current date
        $errors = $this->service->validatePdpaCompliance($request);

        $this->assertNotEmpty($errors);
        $hasOverdueError = false;
        foreach ($errors as $error) {
            if (str_contains(strtolower($error), 'overdue')) {
                $hasOverdueError = true;
                $this->assertStringContainsString('Section 30', $error);
                break;
            }
        }
        $this->assertTrue($hasOverdueError, 'Should have overdue error');
    }

    #[Test]
    public function it_validates_identity_verification_for_access_requests(): void
    {
        $submittedAt = new DateTimeImmutable();
        $request = $this->createRequest($submittedAt, type: RequestType::ACCESS);

        $errors = $this->service->validatePdpaCompliance($request);

        $hasIdentityError = false;
        foreach ($errors as $error) {
            if (str_contains($error, 'Identity')) {
                $hasIdentityError = true;
                break;
            }
        }

        $this->assertTrue($hasIdentityError, 'Should require identity verification');
    }

    #[Test]
    public function it_passes_validation_for_verified_access_request(): void
    {
        $submittedAt = new DateTimeImmutable();
        $request = $this->createRequest($submittedAt, type: RequestType::ACCESS, status: RequestStatus::IN_PROGRESS, metadata: [
            'identity_verified' => true,
        ]);

        $errors = $this->service->validatePdpaCompliance($request);

        $hasIdentityError = false;
        foreach ($errors as $error) {
            if (str_contains($error, 'Identity')) {
                $hasIdentityError = true;
                break;
            }
        }

        $this->assertFalse($hasIdentityError, 'Should not require identity verification when already verified');
    }

    #[Test]
    public function it_gets_requests_approaching_deadline(): void
    {
        $now = new DateTimeImmutable();
        // Due in 3 days
        $approachingRequest = $this->createRequest($now->modify('-18 days'));
        // Due in 15 days
        $notApproachingRequest = $this->createRequest($now->modify('-6 days'));

        $this->requestManager->method('getActiveRequests')
            ->willReturn([$approachingRequest, $notApproachingRequest]);

        $approaching = $this->service->getRequestsApproachingDeadline(5);

        $this->assertCount(1, $approaching);
        $this->assertSame($approachingRequest, $approaching[0]);
    }

    #[Test]
    public function it_provides_compliance_summary(): void
    {
        $now = new DateTimeImmutable();
        $requests = [
            $this->createRequest($now->modify('-25 days')),  // Overdue
            $this->createRequest($now->modify('-19 days')),  // Approaching (2 days left)
            $this->createRequest($now->modify('-10 days')),  // On track
        ];

        $this->requestManager->method('getActiveRequests')
            ->willReturn($requests);

        $summary = $this->service->getComplianceSummary();

        $this->assertEquals(3, $summary['total_pending']);
        $this->assertEquals(1, $summary['overdue']);
        $this->assertEquals(1, $summary['approaching_deadline']);
        $this->assertEquals(1, $summary['on_track']);
        $this->assertEquals('PDPA 2010', $summary['regulation']);
        $this->assertEquals(21, $summary['deadline_days']);
        $this->assertArrayHasKey('compliance_rate', $summary);
        $this->assertArrayHasKey('average_days_remaining', $summary);
    }

    #[Test]
    public function it_returns_100_percent_compliance_when_no_requests(): void
    {
        $this->requestManager->method('getActiveRequests')
            ->willReturn([]);

        $summary = $this->service->getComplianceSummary();

        $this->assertEquals(100.0, $summary['compliance_rate']);
        $this->assertEquals(0, $summary['total_pending']);
    }

    #[Test]
    public function it_validates_processing_principles_consent(): void
    {
        $violations = $this->service->validateProcessingPrinciples([
            'consent_obtained' => false,
            // No legal basis either
        ]);

        $hasConsentViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation, 'General Principle') || str_contains($violation, 'consent')) {
                $hasConsentViolation = true;
                break;
            }
        }

        $this->assertTrue($hasConsentViolation);
    }

    #[Test]
    public function it_validates_processing_with_legal_basis(): void
    {
        $violations = $this->service->validateProcessingPrinciples([
            'consent_obtained' => false,
            'legal_basis' => 'contract',
            'privacy_notice_provided' => true,
        ]);

        $hasConsentViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation, 'General Principle')) {
                $hasConsentViolation = true;
                break;
            }
        }

        $this->assertFalse($hasConsentViolation, 'Contract is valid legal basis');
    }

    #[Test]
    public function it_validates_notice_and_choice_principle(): void
    {
        $violations = $this->service->validateProcessingPrinciples([
            'consent_obtained' => true,
            'privacy_notice_provided' => false,
        ]);

        $hasNoticeViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation, 'Notice')) {
                $hasNoticeViolation = true;
                break;
            }
        }

        $this->assertTrue($hasNoticeViolation);
    }

    #[Test]
    public function it_validates_security_principle(): void
    {
        $violations = $this->service->validateProcessingPrinciples([
            'consent_obtained' => true,
            'privacy_notice_provided' => true,
            'security_measures' => [], // Empty security measures
        ]);

        $hasSecurityViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation, 'Security')) {
                $hasSecurityViolation = true;
                break;
            }
        }

        $this->assertTrue($hasSecurityViolation);
    }

    #[Test]
    public function it_validates_retention_principle(): void
    {
        $violations = $this->service->validateProcessingPrinciples([
            'consent_obtained' => true,
            'privacy_notice_provided' => true,
            'purpose' => 'marketing',
            'retention_period' => 1000, // Exceeds 365 for marketing
        ]);

        $hasRetentionWarning = false;
        foreach ($violations as $violation) {
            if (str_contains($violation, 'Retention')) {
                $hasRetentionWarning = true;
                break;
            }
        }

        $this->assertTrue($hasRetentionWarning);
    }

    #[Test]
    public function it_validates_data_integrity_principle(): void
    {
        $violations = $this->service->validateProcessingPrinciples([
            'consent_obtained' => true,
            'privacy_notice_provided' => true,
            'data_accuracy_verified' => false,
        ]);

        $hasIntegrityViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation, 'Data Integrity')) {
                $hasIntegrityViolation = true;
                break;
            }
        }

        $this->assertTrue($hasIntegrityViolation);
    }

    #[Test]
    public function it_passes_all_validations_when_compliant(): void
    {
        $violations = $this->service->validateProcessingPrinciples([
            'consent_obtained' => true,
            'privacy_notice_provided' => true,
            'security_measures' => ['encryption', 'access_control', 'audit_logging'],
            'purpose' => 'contract',
            'retention_period' => 365,
            'data_accuracy_verified' => true,
        ]);

        $this->assertEmpty($violations);
    }

    private function createRequest(
        DateTimeImmutable $submittedAt,
        RequestType $type = RequestType::ACCESS,
        RequestStatus $status = RequestStatus::PENDING,
        array $metadata = []
    ): DataSubjectRequest {
        $dataSubjectId = new DataSubjectId('party:ds-001');
        $deadline = $submittedAt->modify('+21 days');
        
        // Handle rejected status with rejection reason
        $rejectionReason = ($status === RequestStatus::REJECTED) ? 'Test rejection' : null;
        
        return new DataSubjectRequest(
            id: 'req-' . uniqid(),
            dataSubjectId: $dataSubjectId,
            type: $type,
            status: $status,
            submittedAt: $submittedAt,
            deadline: $deadline,
            rejectionReason: $rejectionReason,
            metadata: $metadata
        );
    }
}
