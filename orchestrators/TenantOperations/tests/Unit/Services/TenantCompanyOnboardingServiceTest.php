<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Tests\Unit\Services;

use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;
use Nexus\TenantOperations\Contracts\TenantCreatorAdapterInterface;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingRequest;
use Nexus\TenantOperations\Services\TenantCompanyOnboardingService;
use PHPUnit\Framework\TestCase;

final class TenantCompanyOnboardingServiceTest extends TestCase
{
    public function testOnboardCreatesTenantAndOwnerUser(): void
    {
        $tenantCreator = $this->createMock(TenantCreatorAdapterInterface::class);
        $tenantCreator
            ->expects($this->once())
            ->method('create')
            ->with(
                'acme',
                'Acme Corp',
                'owner@acme.test',
                'acme.local',
                'Asia/Kuala_Lumpur',
                'en_MY',
                'MYR',
                ['source' => 'web'],
            )
            ->willReturn('tenant-1');

        $adminCreator = $this->createMock(AdminCreatorAdapterInterface::class);
        $adminCreator
            ->expects($this->once())
            ->method('create')
            ->with(
                'tenant-1',
                'owner@acme.test',
                'secret123',
                'Ada',
                'Lovelace',
                true,
                'en_MY',
                'Asia/Kuala_Lumpur',
                ['source' => 'web'],
            )
            ->willReturn('user-1');

        $service = new TenantCompanyOnboardingService($tenantCreator, $adminCreator);

        $result = $service->onboard(new TenantCompanyOnboardingRequest(
            tenantCode: 'acme',
            companyName: 'Acme Corp',
            ownerName: 'Ada Lovelace',
            ownerEmail: 'owner@acme.test',
            ownerPassword: 'secret123',
            timezone: 'Asia/Kuala_Lumpur',
            locale: 'en_MY',
            currency: 'MYR',
            metadata: ['source' => 'web'],
        ));

        $this->assertTrue($result->isSuccess());
        $this->assertSame('tenant-1', $result->tenantId);
        $this->assertSame('user-1', $result->ownerUserId);
        $this->assertSame('tenant-1', $result->getData()['tenant_id']);
        $this->assertSame('user-1', $result->getData()['owner_user_id']);
        $this->assertSame('acme.local', $result->getData()['bootstrap']['tenant_domain']);
    }

    public function testOnboardReturnsFailureWhenOwnerCreationFails(): void
    {
        $tenantCreator = $this->createMock(TenantCreatorAdapterInterface::class);
        $tenantCreator
            ->expects($this->once())
            ->method('create')
            ->willReturn('tenant-1');

        $adminCreator = $this->createMock(AdminCreatorAdapterInterface::class);
        $adminCreator
            ->expects($this->once())
            ->method('create')
            ->willReturn('');

        $service = new TenantCompanyOnboardingService($tenantCreator, $adminCreator);

        $result = $service->onboard(new TenantCompanyOnboardingRequest(
            tenantCode: 'acme',
            companyName: 'Acme Corp',
            ownerName: 'Ada Lovelace',
            ownerEmail: 'owner@acme.test',
            ownerPassword: 'secret123',
        ));

        $this->assertFalse($result->isSuccess());
        $this->assertSame('Company onboarding failed', $result->getMessage());
        $this->assertSame('owner_creation', $result->getIssues()[0]['rule']);
    }
}
