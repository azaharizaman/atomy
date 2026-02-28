<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Setting;
use App\Service\TenantContext;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Setting\Contracts\SettingRepositoryInterface;

/**
 * Setting Repository.
 * 
 * @extends ServiceEntityRepository<Setting>
 */
final class SettingRepository extends ServiceEntityRepository implements SettingRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly TenantContext $tenantContext
    ) {
        parent::__construct($registry, Setting::class);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        // Tenant inheritance:
        // 1. Check for tenant-specific setting
        if ($tenantId !== null) {
            $setting = $this->findOneBy(['key' => $key, 'tenantId' => $tenantId]);
            if ($setting) return $setting->getValue();
        }

        // 2. Check for global setting (no tenant ID)
        $setting = $this->findOneBy(['key' => $key, 'tenantId' => null]);
        return $setting ? $setting->getValue() : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $this->setForTenant($key, $value, $tenantId);
    }

    public function setForTenant(string $key, mixed $value, ?string $tenantId): void
    {
        $setting = $this->findOneBy(['key' => $key, 'tenantId' => $tenantId]);
        
        if (!$setting) {
            $setting = new Setting($key, $value);
            $setting->setTenantId($tenantId);
            $this->getEntityManager()->persist($setting);
        } else {
            $setting->setValue($value);
        }

        $this->getEntityManager()->flush();
    }

    public function delete(string $key): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $this->deleteForTenant($key, $tenantId);
    }

    public function deleteForTenant(string $key, ?string $tenantId): void
    {
        $setting = $this->findOneBy(['key' => $key, 'tenantId' => $tenantId]);
        
        if ($setting) {
            $this->getEntityManager()->remove($setting);
            $this->getEntityManager()->flush();
        }
    }

    public function has(string $key): bool
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $criteria = ['key' => $key];
        
        if ($tenantId !== null) {
            $criteria['tenantId'] = $tenantId;
            if ($this->count($criteria) > 0) return true;
        }

        $criteria['tenantId'] = null;
        return $this->count($criteria) > 0;
    }

    public function getAll(): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        $globalSettings = $this->findBy(['tenantId' => null]);
        $tenantSettings = $tenantId !== null ? $this->findBy(['tenantId' => $tenantId]) : [];

        $settings = [];
        foreach ($globalSettings as $s) {
            $settings[$s->getKey()] = $s->getValue();
        }
        foreach ($tenantSettings as $s) {
            $settings[$s->getKey()] = $s->getValue();
        }

        return $settings;
    }

    public function getByPrefix(string $prefix): array
    {
        $all = $this->getAll();
        $filtered = [];
        foreach ($all as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }

    public function getMetadata(string $key): ?array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $setting = $this->findOneBy(['key' => $key, 'tenantId' => $tenantId]);
        
        if (!$setting && $tenantId !== null) {
            $setting = $this->findOneBy(['key' => $key, 'tenantId' => null]);
        }

        if (!$setting) return null;

        return [
            'id' => $setting->getId(),
            'key' => $setting->getKey(),
            'type' => $setting->getType(),
            'scope' => $setting->getScope(),
            'is_encrypted' => $setting->isEncrypted(),
            'is_read_only' => $setting->isReadOnly(),
            'tenant_id' => $setting->getTenantId(),
        ];
    }

    public function bulkSet(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function isWritable(): bool { return true; }
}
