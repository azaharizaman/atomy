<?php

declare(strict_types=1);

namespace Nexus\Payroll\Services;

use Nexus\Payroll\Contracts\ComponentInterface;
use Nexus\Payroll\Contracts\ComponentQueryInterface;
use Nexus\Payroll\Contracts\ComponentPersistInterface;
use Nexus\Payroll\Exceptions\ComponentNotFoundException;

/**
 * Service for managing payroll components (earnings, deductions).
 */
final readonly class ComponentManager
{
    public function __construct(
        private ComponentQueryInterface $componentQuery,
        private ComponentPersistInterface $componentPersist,
    ) {
    }
    
    public function createComponent(array $data): ComponentInterface
    {
        return $this->componentPersist->create($data);
    }
    
    public function updateComponent(string $id, array $data): ComponentInterface
    {
        $component = $this->getComponentById($id);
        
        return $this->componentPersist->update($id, $data);
    }
    
    public function getComponentById(string $id): ComponentInterface
    {
        $component = $this->componentQuery->findById($id);
        
        if (!$component) {
            throw ComponentNotFoundException::forId($id);
        }
        
        return $component;
    }
    
    public function getComponentByCode(string $tenantId, string $code): ComponentInterface
    {
        $component = $this->componentQuery->findByCode($tenantId, $code);
        
        if (!$component) {
            throw ComponentNotFoundException::forCode($tenantId, $code);
        }
        
        return $component;
    }
    
    public function getActiveComponents(string $tenantId, ?string $type = null): array
    {
        return $this->componentQuery->getActiveComponents($tenantId, $type);
    }
    
    public function deleteComponent(string $id): bool
    {
        $component = $this->getComponentById($id);
        
        return $this->componentPersist->delete($id);
    }
}
