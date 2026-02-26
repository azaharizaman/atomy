<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\IdentityOperations\Services\UserAuthenticationService;
use Nexus\IdentityOperations\Services\AuthenticatorInterface;
use Nexus\IdentityOperations\Services\TokenManagerInterface;
use Nexus\IdentityOperations\Services\PasswordChangerInterface;
use Nexus\IdentityOperations\Services\SessionValidatorInterface;
use Nexus\IdentityOperations\Services\AuditLoggerInterface;
use Nexus\IdentityOperations\DTOs\UserContext;
use Nexus\IdentityOperations\DTOs\RefreshTokenPayload;
use Psr\Log\LoggerInterface;

final class UserAuthenticationServiceTest extends TestCase
{
    private AuthenticatorInterface|MockObject $authenticator;
    private TokenManagerInterface|MockObject $tokenManager;
    private PasswordChangerInterface|MockObject $passwordChanger;
    private SessionValidatorInterface|MockObject $sessionValidator;
    private AuditLoggerInterface|MockObject $auditLogger;
    private LoggerInterface|MockObject $logger;
    private UserAuthenticationService $service;

    protected function setUp(): void
    {
        $this->authenticator = $this->createMock(AuthenticatorInterface::class);
        $this->tokenManager = $this->createMock(TokenManagerInterface::class);
        $this->passwordChanger = $this->createMock(PasswordChangerInterface::class);
        $this->sessionValidator = $this->createMock(SessionValidatorInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new UserAuthenticationService(
            $this->authenticator,
            $this->tokenManager,
            $this->passwordChanger,
            $this->sessionValidator,
            $this->auditLogger,
            $this->logger
        );
    }

    public function testAuthenticateSuccessfully(): void
    {
        $userData = [
            'id' => 'user-123',
            'email' => 'test@example.com',
            'status' => 'active',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'permissions' => ['view'],
            'roles' => ['user'],
        ];

        $this->authenticator->expects($this->once())
            ->method('authenticate')
            ->with('test@example.com', 'password', 'tenant-1')
            ->willReturn($userData);

        $this->tokenManager->expects($this->once())
            ->method('generateAccessToken')
            ->with('user-123', 'tenant-1')
            ->willReturn('access-token');

        $this->tokenManager->expects($this->once())
            ->method('generateRefreshToken')
            ->with('user-123', 'tenant-1')
            ->willReturn('refresh-token');

        $this->tokenManager->expects($this->once())
            ->method('createSession')
            ->with('user-123', 'access-token', 'tenant-1')
            ->willReturn('session-1');

        $result = $this->service->authenticate('test@example.com', 'password', 'tenant-1');

        $this->assertEquals('user-123', $result->userId);
        $this->assertEquals('session-1', $result->sessionId);
        $this->assertEquals('access-token', $result->accessToken);
    }

    public function testRefreshTokenSuccessfully(): void
    {
        $payload = new RefreshTokenPayload('user-123', 'tenant-1');
        
        $this->tokenManager->expects($this->once())
            ->method('validateRefreshToken')
            ->with('refresh-token', 'tenant-1')
            ->willReturn($payload);

        $this->authenticator->expects($this->once())
            ->method('getUserById')
            ->with('user-123')
            ->willReturn(['id' => 'user-123', 'email' => 'test@example.com', 'status' => 'active']);

        $this->tokenManager->expects($this->once())
            ->method('generateAccessToken')
            ->with('user-123', 'tenant-1')
            ->willReturn('new-access-token');

        $result = $this->service->refreshToken('refresh-token', 'tenant-1');

        $this->assertEquals('user-123', $result->userId);
        $this->assertEquals('new-access-token', $result->accessToken);
    }

    public function testLogoutSuccessfully(): void
    {
        // logout needs tenantId now if it calls invalidateSession
        $this->tokenManager->expects($this->once())
            ->method('invalidateSession')
            ->with('session-1', 'tenant-1');

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with('user.logged_out', 'user-123');

        $result = $this->service->logout('user-123', 'session-1', 'tenant-1');

        $this->assertTrue($result);
    }

    public function testLogoutAllSessionsSuccessfully(): void
    {
        $this->tokenManager->expects($this->once())
            ->method('invalidateUserSessions')
            ->with('user-123', 'tenant-1');

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with('user.logged_out', 'user-123');

        $result = $this->service->logout('user-123', null, 'tenant-1');

        $this->assertTrue($result);
    }

    public function testLogoutFailure(): void
    {
        $this->tokenManager->expects($this->once())
            ->method('invalidateUserSessions')
            ->with('user-123', 'tenant-1')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->logout('user-123', null, 'tenant-1');

        $this->assertFalse($result);
    }

    public function testValidateSession(): void
    {
        $this->sessionValidator->expects($this->once())
            ->method('isValid')
            ->with('session-1')
            ->willReturn(true);

        $this->assertTrue($this->service->validateSession('session-1'));
    }

    public function testChangePasswordSuccessfully(): void
    {
        $this->passwordChanger->expects($this->once())
            ->method('changeWithVerification')
            ->with('user-123', 'old', 'new');

        $result = $this->service->changePassword('user-123', 'old', 'new');

        $this->assertTrue($result);
    }

    public function testChangePasswordFailure(): void
    {
        $this->passwordChanger->expects($this->once())
            ->method('changeWithVerification')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->changePassword('user-123', 'old', 'new');

        $this->assertFalse($result);
    }

    public function testResetPasswordSuccessfully(): void
    {
        $this->passwordChanger->expects($this->once())
            ->method('resetByAdmin')
            ->with('user-123', 'new');

        $result = $this->service->resetPassword('user-123', 'new', 'admin-1');

        $this->assertTrue($result);
    }

    public function testResetPasswordFailure(): void
    {
        $this->passwordChanger->expects($this->once())
            ->method('resetByAdmin')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->resetPassword('user-123', 'new', 'admin-1');

        $this->assertFalse($result);
    }

    public function testInvalidateAllSessionsSuccessfully(): void
    {
        $this->tokenManager->expects($this->once())
            ->method('invalidateUserSessions')
            ->with('user-123', 'tenant-1');

        $result = $this->service->invalidateAllSessions('user-123', 'tenant-1');

        $this->assertTrue($result);
    }

    public function testInvalidateAllSessionsFailure(): void
    {
        $this->tokenManager->expects($this->once())
            ->method('invalidateUserSessions')
            ->with('user-123', 'tenant-1')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->invalidateAllSessions('user-123', 'tenant-1');

        $this->assertFalse($result);
    }
}
