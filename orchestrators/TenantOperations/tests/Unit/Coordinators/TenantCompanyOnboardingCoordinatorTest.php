<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Tests\Unit\Coordinators;

use Nexus\TenantOperations\Contracts\TenantCompanyOnboardingServiceInterface;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingResult;
use Nexus\TenantOperations\Services\TenantCompanyOnboardingService;
use Nexus\TenantOperations\Coordinators\TenantCompanyOnboardingCoordinator;
use PHPUnit\Framework\TestCase;

final class TenantCompanyOnboardingCoordinatorTest extends TestCase
{
    public function testOnboardDelegatesToService(): void
    {
        $service = $this->createMock(TenantCompanyOnboardingServiceInterface::class);
        $service
            ->expects($this->once())
            ->method('onboard')
            ->with($this->isInstanceOf(TenantCompanyOnboardingRequest::class))
            ->willReturn(TenantCompanyOnboardingResult::success('tenant-1', 'user-1'));

        $coordinator = new TenantCompanyOnboardingCoordinator($service);

        $result = $coordinator->onboard(new TenantCompanyOnboardingRequest(
            tenantCode: 'acme',
            companyName: 'Acme Corp',
            ownerName: 'Ada Lovelace',
            ownerEmail: 'owner@acme.test',
            ownerPassword: 'secret123',
        ));

        $this->assertTrue($result->isSuccess());
        $this->assertSame('tenant-1', $result->tenantId);
        $this->assertSame('user-1', $result->ownerUserId);
    }
}
