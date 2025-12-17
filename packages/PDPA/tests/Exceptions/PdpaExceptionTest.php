<?php

declare(strict_types=1);

namespace Nexus\PDPA\Tests\Exceptions;

use Nexus\PDPA\Exceptions\PdpaException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdpaException::class)]
final class PdpaExceptionTest extends TestCase
{
    #[Test]
    public function it_creates_deadline_exceeded_exception(): void
    {
        $exception = PdpaException::deadlineExceeded(5);

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('21-day', $exception->getMessage());
        $this->assertStringContainsString('5 days', $exception->getMessage());
        $this->assertStringContainsString('Section 30', $exception->getMessage());
    }

    #[Test]
    public function it_creates_extension_limit_exceeded_exception(): void
    {
        $exception = PdpaException::extensionLimitExceeded();

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('14 days', $exception->getMessage());
        $this->assertStringContainsString('already been extended', $exception->getMessage());
    }

    #[Test]
    public function it_creates_invalid_extension_duration_exception(): void
    {
        $exception = PdpaException::invalidExtensionDuration(30);

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('30 days', $exception->getMessage());
        $this->assertStringContainsString('14 days', $exception->getMessage());
    }

    #[Test]
    public function it_creates_consent_required_exception(): void
    {
        $exception = PdpaException::consentRequired('marketing');

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('marketing', $exception->getMessage());
        $this->assertStringContainsString('Section 6', $exception->getMessage());
    }

    #[Test]
    public function it_creates_sensitive_data_violation_exception(): void
    {
        $exception = PdpaException::sensitiveDataViolation('health');

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('health', $exception->getMessage());
        $this->assertStringContainsString('Section 40', $exception->getMessage());
    }

    #[Test]
    public function it_creates_notice_requirement_violation_exception(): void
    {
        $exception = PdpaException::noticeRequirementViolation();

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('notice', $exception->getMessage());
        $this->assertStringContainsString('Section 7', $exception->getMessage());
    }

    #[Test]
    public function it_creates_security_breach_exception(): void
    {
        $exception = PdpaException::securityBreach('Unauthorized access detected');

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('Unauthorized access detected', $exception->getMessage());
        $this->assertStringContainsString('Section 9', $exception->getMessage());
    }

    #[Test]
    public function it_creates_retention_violation_exception(): void
    {
        $exception = PdpaException::retentionViolation(365, 730);

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('365', $exception->getMessage());
        $this->assertStringContainsString('730', $exception->getMessage());
        $this->assertStringContainsString('Section 10', $exception->getMessage());
    }

    #[Test]
    public function it_creates_integrity_violation_exception(): void
    {
        $exception = PdpaException::integrityViolation('Data accuracy cannot be verified');

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('Data accuracy cannot be verified', $exception->getMessage());
        $this->assertStringContainsString('Section 11', $exception->getMessage());
    }

    #[Test]
    public function it_creates_access_principle_violation_exception(): void
    {
        $exception = PdpaException::accessPrincipleViolation('Identity verification failed');

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('Identity verification failed', $exception->getMessage());
        $this->assertStringContainsString('Section 12', $exception->getMessage());
    }

    #[Test]
    public function it_creates_cross_border_transfer_violation_exception(): void
    {
        $exception = PdpaException::crossBorderTransferViolation('China');

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('China', $exception->getMessage());
        $this->assertStringContainsString('Section 129', $exception->getMessage());
    }

    #[Test]
    public function it_creates_request_not_found_exception(): void
    {
        $exception = PdpaException::requestNotFound('req-12345');

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('req-12345', $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    #[Test]
    public function it_creates_invalid_request_status_exception(): void
    {
        $exception = PdpaException::invalidRequestStatus('pending', 'completed');

        $this->assertInstanceOf(PdpaException::class, $exception);
        $this->assertStringContainsString('pending', $exception->getMessage());
        $this->assertStringContainsString('completed', $exception->getMessage());
    }
}
