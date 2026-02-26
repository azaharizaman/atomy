<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\Coordinators\MfaCoordinator;
use Nexus\IdentityOperations\Contracts\MfaServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\MfaEnableRequest;
use Nexus\IdentityOperations\DTOs\MfaEnableResult;
use Nexus\IdentityOperations\DTOs\MfaVerifyRequest;
use Nexus\IdentityOperations\DTOs\MfaVerifyResult;
use Nexus\IdentityOperations\DTOs\MfaDisableRequest;
use Nexus\IdentityOperations\DTOs\MfaDisableResult;
use Nexus\IdentityOperations\DTOs\MfaMethod;
use Psr\Log\LoggerInterface;

final class MfaCoordinatorTest extends TestCase
{
    private $mfaService;
    private $contextDataProvider;
    private $logger;
    private $coordinator;

    protected function setUp(): void
    {
        $this->mfaService = $this->createMock(MfaServiceInterface::class);
        $this->contextDataProvider = $this->createMock(UserContextProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new MfaCoordinator(
            $this->mfaService,
            $this->contextDataProvider,
            $this->logger
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('MfaCoordinator', $this->coordinator->getName());
    }

    public function testHasRequiredData(): void
    {
        $this->contextDataProvider->expects($this->once())
            ->method('userExists')
            ->with('user-1')
            ->willReturn(true);
        $this->assertTrue($this->coordinator->hasRequiredData('user-1'));
    }

    public function testEnable(): void
    {
        $request = new MfaEnableRequest(userId: 'user-123', method: MfaMethod::TOTP);
        $result = MfaEnableResult::success('user-123', 'secret');

        $this->mfaService->expects($this->once())
            ->method('enable')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->enable($request));
    }

    public function testVerify(): void
    {
        $request = new MfaVerifyRequest(userId: 'user-123', code: '123456', method: MfaMethod::TOTP);
        $result = MfaVerifyResult::success('user-123');

        $this->mfaService->expects($this->once())
            ->method('verify')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->verify($request));
    }

    public function testDisable(): void
    {
        $request = new MfaDisableRequest(userId: 'user-123', disabledBy: 'admin-1');
        $result = MfaDisableResult::success('user-123');

        $this->mfaService->expects($this->once())
            ->method('disable')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->disable($request));
    }

    public function testGetStatus(): void
    {
        $status = ['totp' => true];
        $this->mfaService->expects($this->once())
            ->method('getStatus')
            ->with('user-1')
            ->willReturn($status);

        $this->assertEquals($status, $this->coordinator->getStatus('user-1'));
    }

    public function testGenerateBackupCodes(): void
    {
        $codes = ['1', '2'];
        $this->mfaService->expects($this->once())
            ->method('generateBackupCodes')
            ->with('user-1')
            ->willReturn($codes);

        $this->assertEquals($codes, $this->coordinator->generateBackupCodes('user-1'));
    }

    public function testValidateBackupCode(): void
    {
        $this->mfaService->expects($this->once())
            ->method('validateBackupCode')
            ->with('user-1', 'code')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->validateBackupCode('user-1', 'code'));
    }
}
