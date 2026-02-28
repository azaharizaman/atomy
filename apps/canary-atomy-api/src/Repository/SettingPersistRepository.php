<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Setting;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Setting\Contracts\SettingRepositoryInterface;

/**
 * Setting Repository (Persistence/Write Concerns).
 * 
 * Implements atomic upsert for settings.
 */
final readonly class SettingPersistRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TenantContext $tenantContext
    ) {}

    public function set(string $key, mixed $value): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $this->setForTenant($key, $value, $tenantId);
    }

    public function setForTenant(string $key, mixed $value, ?string $tenantId): void
    {
        $this->entityManager->wrapInTransaction(function() use ($key, $value, $tenantId) {
            $setting = $this->entityManager->getRepository(Setting::class)->findOneBy([
                'key' => $key,
                'tenantId' => $tenantId
            ]);

            if (!$setting) {
                $setting = new Setting($key, $value);
                $setting->setTenantId($tenantId);
                $this->entityManager->persist($setting);
            } else {
                $setting->setValue($value);
            }

            $this->entityManager->flush();
        });
    }

    public function delete(string $key): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $this->deleteForTenant($key, $tenantId);
    }

    public function deleteForTenant(string $key, ?string $tenantId): void
    {
        $this->entityManager->wrapInTransaction(function() use ($key, $tenantId) {
            $setting = $this->entityManager->getRepository(Setting::class)->findOneBy([
                'key' => $key,
                'tenantId' => $tenantId
            ]);

            if ($setting) {
                $this->entityManager->remove($setting);
                $this->entityManager->flush();
            }
        });
    }

    public function bulkSet(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }
}
