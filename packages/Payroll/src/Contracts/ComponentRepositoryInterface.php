<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Repository contract for payroll component persistence.
 */
interface ComponentRepositoryInterface
{
    public function findById(string $id): ?ComponentInterface;
    
    public function findByCode(string $tenantId, string $code): ?ComponentInterface;
    
    public function getActiveComponents(string $tenantId, ?string $type = null): array;
    
    public function create(array $data): ComponentInterface;
    
    public function update(string $id, array $data): ComponentInterface;
    
    public function delete(string $id): bool;
}
