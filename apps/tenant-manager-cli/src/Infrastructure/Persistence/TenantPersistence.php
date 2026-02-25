<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantPersistenceInterface;

final readonly class TenantPersistence implements TenantPersistenceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function create(array $data): TenantInterface
    {
        $tenant = new Tenant(
            code: $data['code'],
            name: $data['name'],
            email: $data['email']
        );

        if (isset($data['status'])) {
            $tenant->setStatus($data['status']);
        }

        $this->entityManager->persist($tenant);
        $this->entityManager->flush();

        return $tenant;
    }

    public function update(string $id, array $data): TenantInterface
    {
        $tenant = $this->entityManager->find(Tenant::class, $id);
        
        if (!$tenant) {
            throw new \RuntimeException("Tenant not found with ID: $id");
        }

        // Basic implementation for Canary app
        $this->entityManager->flush();

        return $tenant;
    }

    public function delete(string $id): bool
    {
        $tenant = $this->entityManager->find(Tenant::class, $id);
        if ($tenant) {
            $this->entityManager->remove($tenant);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function forceDelete(string $id): bool
    {
        return $this->delete($id);
    }

    public function restore(string $id): TenantInterface
    {
        throw new \BadMethodCallException('Restore not implemented yet');
    }
}
