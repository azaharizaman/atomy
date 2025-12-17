<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Exceptions;

use DateTimeImmutable;
use Nexus\AmlCompliance\Exceptions\SarGenerationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SarGenerationFailedException::class)]
final class SarGenerationFailedExceptionTest extends TestCase
{
    public function test_missing_required_data_factory(): void
    {
        $exception = SarGenerationFailedException::missingRequiredData(
            sarId: 'sar-123',
            missingFields: ['narrative', 'subject_name']
        );

        $this->assertStringContainsString('sar-123', $exception->getMessage());
        $this->assertStringContainsString('narrative', $exception->getMessage());
        $this->assertSame(4001, $exception->getCode());
    }

    public function test_invalid_transition_factory(): void
    {
        $exception = SarGenerationFailedException::invalidTransition(
            sarId: 'sar-123',
            fromStatus: 'draft',
            toStatus: 'submitted'
        );

        $this->assertStringContainsString('sar-123', $exception->getMessage());
        $this->assertStringContainsString('draft', $exception->getMessage());
        $this->assertStringContainsString('submitted', $exception->getMessage());
        $this->assertSame(4002, $exception->getCode());
    }

    public function test_filing_deadline_exceeded_factory(): void
    {
        $deadline = new DateTimeImmutable('2024-01-01');
        $now = new DateTimeImmutable('2024-01-10');

        $exception = SarGenerationFailedException::filingDeadlineExceeded(
            sarId: 'sar-123',
            deadline: $deadline,
            now: $now
        );

        $this->assertStringContainsString('sar-123', $exception->getMessage());
        $this->assertSame(4003, $exception->getCode());
    }

    public function test_not_found_factory(): void
    {
        $exception = SarGenerationFailedException::notFound(sarId: 'sar-123');

        $this->assertStringContainsString('sar-123', $exception->getMessage());
        $this->assertSame(4004, $exception->getCode());
    }

    public function test_already_submitted_factory(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01 10:00:00');

        $exception = SarGenerationFailedException::alreadySubmitted(
            sarId: 'sar-123',
            submittedAt: $submittedAt
        );

        $this->assertStringContainsString('sar-123', $exception->getMessage());
        $this->assertSame(4005, $exception->getCode());
    }

    public function test_invalid_narrative_factory(): void
    {
        $exception = SarGenerationFailedException::invalidNarrative(
            sarId: 'sar-123',
            reason: 'Too short - minimum 100 characters required'
        );

        $this->assertStringContainsString('sar-123', $exception->getMessage());
        $this->assertStringContainsString('Too short', $exception->getMessage());
        $this->assertSame(4006, $exception->getCode());
    }

    public function test_insufficient_evidence_factory(): void
    {
        $exception = SarGenerationFailedException::insufficientEvidence(
            context: ['transactions' => 0, 'documents' => 0]
        );

        $this->assertStringContainsString('Insufficient evidence', $exception->getMessage());
        $this->assertSame(4007, $exception->getCode());
    }

    public function test_approval_required_factory(): void
    {
        $exception = SarGenerationFailedException::approvalRequired(
            sarId: 'sar-123',
            requiredApprover: 'compliance_officer'
        );

        $this->assertStringContainsString('sar-123', $exception->getMessage());
        $this->assertStringContainsString('compliance_officer', $exception->getMessage());
        $this->assertSame(4008, $exception->getCode());
    }

    public function test_filing_service_error_factory(): void
    {
        $exception = SarGenerationFailedException::filingServiceError(
            sarId: 'sar-123',
            serviceError: 'Connection refused'
        );

        $this->assertStringContainsString('sar-123', $exception->getMessage());
        $this->assertStringContainsString('Connection refused', $exception->getMessage());
        $this->assertSame(4009, $exception->getCode());
    }

    public function test_duplicate_sar_factory(): void
    {
        $exception = SarGenerationFailedException::duplicateSar(
            partyId: 'party-123',
            existingSarId: 'existing-sar-456'
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('existing-sar-456', $exception->getMessage());
        $this->assertSame(4010, $exception->getCode());
    }
}
