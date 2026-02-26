<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\IdentityOperations\Services\MfaService;
use Nexus\IdentityOperations\Services\MfaEnrollerInterface;
use Nexus\IdentityOperations\Services\MfaVerifierInterface;
use Nexus\IdentityOperations\Services\MfaDisablerInterface;
use Nexus\IdentityOperations\Services\BackupCodeGeneratorInterface;
use Nexus\IdentityOperations\Services\AuditLoggerInterface;
use Nexus\IdentityOperations\DTOs\MfaEnableRequest;
use Nexus\IdentityOperations\DTOs\MfaEnableResult;
use Nexus\IdentityOperations\DTOs\MfaVerifyRequest;
use Nexus\IdentityOperations\DTOs\MfaDisableRequest;
use Nexus\IdentityOperations\DTOs\MfaMethod;
use Psr\Log\LoggerInterface;

final class MfaServiceTest extends TestCase
{
    private readonly MfaEnrollerInterface|MockObject $enroller;
    private readonly MfaVerifierInterface|MockObject $verifier;
    private readonly MfaDisablerInterface|MockObject $disabler;
    private readonly BackupCodeGeneratorInterface|MockObject $backupCodeGenerator;
    private readonly AuditLoggerInterface|MockObject $auditLogger;
    private readonly LoggerInterface|MockObject $logger;
    private readonly MfaService $service;

    protected function setUp(): void
    {
        $this->enroller = $this->createMock(MfaEnrollerInterface::class);
        $this->verifier = $this->createMock(MfaVerifierInterface::class);
        $this->disabler = $this->createMock(MfaDisablerInterface::class);
        $this->backupCodeGenerator = $this->createMock(BackupCodeGeneratorInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new MfaService(
            $this->enroller,
            $this->verifier,
            $this->disabler,
            $this->backupCodeGenerator,
            $this->auditLogger,
            $this->logger
        );
    }

    public function testEnableTotpSuccessfully(): void
    {
        $request = new MfaEnableRequest(
            userId: 'user-123',
            tenantId: 'tenant-1',
            method: MfaMethod::TOTP
        );

        $result = MfaEnableResult::success('user-123', 'base32secret', 'otpauth://...');

        $this->enroller->expects($this->once())
            ->method('enroll')
            ->with('user-123', 'tenant-1', MfaMethod::TOTP)
            ->willReturn($result);

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with('mfa.enabled', 'user-123');

        $actualResult = $this->service->enable($request);

        $this->assertTrue($actualResult->success);
        $this->assertEquals('base32secret', $actualResult->secret);
    }

    public function testEnableFailure(): void
    {
        $request = new MfaEnableRequest(userId: 'user-1', tenantId: 'tenant-1', method: MfaMethod::TOTP);
        $this->enroller->expects($this->once())
            ->method('enroll')
            ->with('user-1', 'tenant-1', MfaMethod::TOTP)
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->enable($request);
        $this->assertFalse($result->success);
    }

    public function testVerifyMfaSuccessfully(): void
    {
        $request = new MfaVerifyRequest(
            userId: 'user-123',
            code: '123456',
            method: MfaMethod::TOTP
        );

        $this->verifier->expects($this->once())
            ->method('verify')
            ->with('user-123', MfaMethod::TOTP, '123456')
            ->willReturn(true);

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with('mfa.verified', 'user-123');

        $result = $this->service->verify($request);

        $this->assertTrue($result->success);
    }

    public function testVerifyMfaFailure(): void
    {
        $request = new MfaVerifyRequest(
            userId: 'user-123',
            code: 'wrong',
            method: MfaMethod::TOTP
        );

        $this->verifier->expects($this->once())
            ->method('verify')
            ->willReturn(false);

        $this->verifier->expects($this->once())
            ->method('getFailedAttempts')
            ->with('user-123')
            ->willReturn(1);

        $result = $this->service->verify($request);

        $this->assertFalse($result->success);
        $this->assertEquals(4, $result->remainingAttempts);
    }

    public function testDisableMfaSuccessfully(): void
    {
        $request = new MfaDisableRequest(
            userId: 'user-123',
            disabledBy: 'admin-1',
            reason: 'Lost device'
        );

        $this->disabler->expects($this->once())
            ->method('disable')
            ->with('user-123');

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with('mfa.disabled', 'user-123');

        $result = $this->service->disable($request);

        $this->assertTrue($result->success);
    }

    public function testDisableMfaFailure(): void
    {
        $request = new MfaDisableRequest(userId: 'user-1', disabledBy: 'admin-1');
        $this->disabler->expects($this->once())
            ->method('disable')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->disable($request);
        $this->assertFalse($result->success);
    }

    public function testGetStatus(): void
    {
        $statusResult = new MfaStatusResult('user-1', ['totp' => true]);
        $this->enroller->expects($this->once())
            ->method('getStatus')
            ->with('user-1', 'tenant-1')
            ->willReturn($statusResult);

        $result = $this->service->getStatus('user-1', 'tenant-1');
        $this->assertSame($statusResult, $result);
        $this->assertTrue($result->isEnrolled(MfaMethod::TOTP));
    }

    public function testGenerateBackupCodes(): void
    {
        $codes = ['1', '2'];
        $this->backupCodeGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($codes);

        $this->assertEquals($codes, $this->service->generateBackupCodes('user-1'));
    }

    public function testValidateBackupCode(): void
    {
        $this->verifier->expects($this->once())
            ->method('verifyBackupCode')
            ->with('user-1', 'code')
            ->willReturn(true);

        $this->assertTrue($this->service->validateBackupCode('user-1', 'code'));
    }
}
