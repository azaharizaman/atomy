<?php

declare(strict_types=1);

namespace Nexus\Tenant\Tests\Unit\Services;

use Nexus\Tenant\Contracts\EventDispatcherInterface;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;
use Nexus\Tenant\Contracts\TenantValidationInterface;
use Nexus\Tenant\Enums\TenantStatus;
use Nexus\Tenant\Events\TenantCreatedEvent;
use Nexus\Tenant\Exceptions\DuplicateTenantNameException;
use Nexus\Tenant\Services\TenantLifecycleService;
use PHPUnit\Framework\TestCase;

final class TenantLifecycleServiceTest extends TestCase
{
    public function testCreateTenantRejectsDuplicateName(): void
    {
        $persistence = $this->createMock(TenantPersistenceInterface::class);
        $persistence->expects($this->never())->method('create');

        $query = $this->createMock(TenantQueryInterface::class);

        $validation = $this->createMock(TenantValidationInterface::class);
        $validation->method('codeExists')->willReturn(false);
        $validation->method('domainExists')->willReturn(false);
        $validation->method('nameExists')->willReturn(true);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $service = new TenantLifecycleService($persistence, $query, $validation, $dispatcher);

        $this->expectException(DuplicateTenantNameException::class);

        $service->createTenant('ACME', 'Acme Corp', 'admin@acme.test', null, []);
    }

    public function testCreateTenantPersistsWithDefaultsWhenUnique(): void
    {
        $tenant = $this->createMock(TenantInterface::class);
        $tenant->method('getId')->willReturn('tenant-1');
        $tenant->method('getCode')->willReturn('ACME');
        $tenant->method('getName')->willReturn('Acme Corp');
        $tenant->method('getDomain')->willReturn('acme.test');
        $tenant->method('getMetadata')->willReturn([]);

        $persistence = $this->createMock(TenantPersistenceInterface::class);
        $persistence
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return $data['code'] === 'ACME'
                    && $data['name'] === 'Acme Corp'
                    && $data['email'] === 'admin@acme.test'
                    && $data['domain'] === 'acme.test'
                    && $data['status'] === TenantStatus::Pending->value;
            }))
            ->willReturn($tenant);

        $query = $this->createMock(TenantQueryInterface::class);

        $validation = $this->createMock(TenantValidationInterface::class);
        $validation->method('codeExists')->willReturn(false);
        $validation->method('domainExists')->willReturn(false);
        $validation->method('nameExists')->willReturn(false);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) use ($tenant): bool {
                return $event instanceof TenantCreatedEvent && $event->tenant === $tenant;
            }));

        $service = new TenantLifecycleService($persistence, $query, $validation, $dispatcher);

        $result = $service->createTenant('ACME', 'Acme Corp', 'admin@acme.test', 'acme.test', []);

        $this->assertSame($tenant, $result);
    }
}
