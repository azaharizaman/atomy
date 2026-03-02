<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Setting;
use App\Service\TenantContext;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Setting\Contracts\SettingRepositoryInterface;

/**
 * Setting Repository (Query/Read Concerns).
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
        
        if ($tenantId !== null) {
            $setting = $this->findOneBy(['key' => $key, 'tenantId' => $tenantId]);
            if ($setting) return $setting->getValue();
        }

        $setting = $this->findOneBy(['key' => $key, 'tenantId' => null]);
        return $setting ? $setting->getValue() : $default;
    }

    public function has(string $key): bool
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        if ($tenantId !== null && $this->count(['key' => $key, 'tenantId' => $tenantId]) > 0) {
            return true;
        }

        return $this->count(['key' => $key, 'tenantId' => null]) > 0;
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

    /**
     * @deprecated Use SettingPersistRepository::set()
     */
    public function set(string $key, mixed $value): void { throw new \LogicException('Use SettingPersistRepository for writes'); }

    /**
     * @deprecated Use SettingPersistRepository::delete()
     */
    public function delete(string $key): void { throw new \LogicException('Use SettingPersistRepository for writes'); }

    /**
     * @deprecated Use SettingPersistRepository::bulkSet()
     */
    public function bulkSet(array $settings): void { throw new \LogicException('Use SettingPersistRepository for writes'); }

    public function isWritable(): bool { return false; }
}
