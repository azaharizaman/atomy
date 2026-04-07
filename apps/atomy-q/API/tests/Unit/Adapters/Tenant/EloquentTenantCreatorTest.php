<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Tenant;

use App\Models\Tenant;
use App\Adapters\Tenant\EloquentTenantCreator;
use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\Tenant\Contracts\TenantValidationInterface;
use Nexus\Tenant\Exceptions\DuplicateTenantCodeException;
use Nexus\Tenant\Exceptions\DuplicateTenantDomainException;
use Nexus\Tenant\Exceptions\DuplicateTenantNameException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class EloquentTenantCreatorTest extends TestCase
{
    public function test_it_normalizes_and_persists_tenant_data(): void
    {
        $persistedTenant = new Tenant();
        $persistedTenant->forceFill(['id' => 'tenant-123']);

        $persistence = new class($persistedTenant) implements TenantPersistenceInterface {
            public array $payload = [];

            public function __construct(private Tenant $tenant)
            {
            }

            public function create(array $data): \Nexus\Tenant\Contracts\TenantInterface
            {
                $this->payload = $data;

                return $this->tenant;
            }

            public function update(string $id, array $data): \Nexus\Tenant\Contracts\TenantInterface
            {
                throw new \BadMethodCallException('Not used');
            }

            public function delete(string $id): bool
            {
                throw new \BadMethodCallException('Not used');
            }

            public function forceDelete(string $id): bool
            {
                throw new \BadMethodCallException('Not used');
            }

            public function restore(string $id): \Nexus\Tenant\Contracts\TenantInterface
            {
                throw new \BadMethodCallException('Not used');
            }
        };

        $validation = new class implements TenantValidationInterface {
            public function codeExists(string $code, ?string $excludeId = null): bool
            {
                return false;
            }

            public function domainExists(string $domain, ?string $excludeId = null): bool
            {
                return false;
            }

            public function nameExists(string $name, ?string $excludeId = null): bool
            {
                return false;
            }
        };

        $creator = new EloquentTenantCreator($persistence, $validation);
        $tenantId = $creator->create(
            code: '  acme-hq  ',
            name: '  Acme Procurement Ltd  ',
            email: '  OWNER@ACME.TEST  ',
            domain: '  ',
            timezone: '  Asia/Kuala_Lumpur  ',
            locale: '  en-MY  ',
            currency: ' myr ',
            metadata: ['source' => 'register-company'],
        );

        $this->assertSame('tenant-123', $tenantId);
        $this->assertSame([
            'code' => 'ACME-HQ',
            'name' => 'Acme Procurement Ltd',
            'email' => 'owner@acme.test',
            'domain' => 'tenant.local',
            'subdomain' => 'tenant',
            'status' => 'active',
            'timezone' => 'Asia/Kuala_Lumpur',
            'locale' => 'en-MY',
            'currency' => 'myr',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'metadata' => ['source' => 'register-company'],
            'onboarding_progress' => 100,
        ], $persistence->payload);
    }

    public static function duplicateProvider(): array
    {
        return [
            'code' => ['codeExists', DuplicateTenantCodeException::class],
            'name' => ['nameExists', DuplicateTenantNameException::class],
            'domain' => ['domainExists', DuplicateTenantDomainException::class],
        ];
    }

    #[DataProvider('duplicateProvider')]
    public function test_it_throws_for_duplicate_tenant_values(string $method, string $exceptionClass): void
    {
        $persistence = new class implements TenantPersistenceInterface {
            public function create(array $data): \Nexus\Tenant\Contracts\TenantInterface
            {
                throw new \BadMethodCallException('Not used');
            }

            public function update(string $id, array $data): \Nexus\Tenant\Contracts\TenantInterface
            {
                throw new \BadMethodCallException('Not used');
            }

            public function delete(string $id): bool
            {
                throw new \BadMethodCallException('Not used');
            }

            public function forceDelete(string $id): bool
            {
                throw new \BadMethodCallException('Not used');
            }

            public function restore(string $id): \Nexus\Tenant\Contracts\TenantInterface
            {
                throw new \BadMethodCallException('Not used');
            }
        };

        $validation = new class($method) implements TenantValidationInterface {
            public function __construct(private string $method)
            {
            }

            public function codeExists(string $code, ?string $excludeId = null): bool
            {
                return $this->method === 'codeExists';
            }

            public function domainExists(string $domain, ?string $excludeId = null): bool
            {
                return $this->method === 'domainExists';
            }

            public function nameExists(string $name, ?string $excludeId = null): bool
            {
                return $this->method === 'nameExists';
            }
        };

        $creator = new EloquentTenantCreator($persistence, $validation);

        $this->expectException($exceptionClass);
        $creator->create(
            code: 'ACME',
            name: 'Acme Procurement Ltd',
            email: 'owner@acme.test',
            domain: 'acme.test'
        );
    }
}
