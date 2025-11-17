<?php

declare(strict_types=1);

namespace App\Repositories\Payroll;

use App\Models\PayrollComponent;
use Nexus\Payroll\Contracts\ComponentInterface;
use Nexus\Payroll\Contracts\ComponentRepositoryInterface;
use Nexus\Payroll\Exceptions\ComponentNotFoundException;

class EloquentComponentRepository implements ComponentRepositoryInterface
{
    public function findById(string $id): ComponentInterface
    {
        $component = PayrollComponent::find($id);
        
        if (!$component) {
            throw ComponentNotFoundException::forId($id);
        }
        
        return $component;
    }

    public function findByCode(string $tenantId, string $code): ComponentInterface
    {
        $component = PayrollComponent::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        
        if (!$component) {
            throw ComponentNotFoundException::forCode($code);
        }
        
        return $component;
    }

    public function findActiveByType(string $tenantId, string $type): array
    {
        return PayrollComponent::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->all();
    }

    public function findNonStatutoryComponents(string $tenantId): array
    {
        return PayrollComponent::where('tenant_id', $tenantId)
            ->where('is_statutory', false)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->all();
    }

    public function getAllForTenant(string $tenantId, array $filters = []): array
    {
        $query = PayrollComponent::where('tenant_id', $tenantId);
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['is_statutory'])) {
            $query->where('is_statutory', $filters['is_statutory']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        return $query->orderBy('display_order')->get()->all();
    }

    public function save(ComponentInterface $component): ComponentInterface
    {
        if ($component instanceof PayrollComponent) {
            $component->save();
            return $component;
        }
        
        throw new \InvalidArgumentException('Component must be an Eloquent model');
    }

    public function delete(string $id): void
    {
        $component = PayrollComponent::find($id);
        
        if (!$component) {
            throw ComponentNotFoundException::forId($id);
        }
        
        $component->delete();
    }

    public function codeExists(string $tenantId, string $code, ?string $excludeId = null): bool
    {
        $query = PayrollComponent::where('tenant_id', $tenantId)
            ->where('code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}
