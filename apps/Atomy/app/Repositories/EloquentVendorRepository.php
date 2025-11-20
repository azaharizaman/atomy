<?php

declare(strict_types=1);

namespace Atomy\Repositories;

use Atomy\Models\Vendor;
use Nexus\Payable\Contracts\VendorRepositoryInterface;
use Nexus\Payable\Contracts\VendorInterface;
use Illuminate\Support\Str;

/**
 * Eloquent vendor repository implementation.
 */
final class EloquentVendorRepository implements VendorRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findById(string $vendorId): ?VendorInterface
    {
        return Vendor::find($vendorId);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $tenantId, string $code): ?VendorInterface
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByTaxId(string $tenantId, string $taxId): ?VendorInterface
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('tax_id', $taxId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(string $tenantId, array $filters = []): array
    {
        $query = Vendor::where('tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('code')->get()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $tenantId, array $data): VendorInterface
    {
        $data['id'] = Str::uuid()->toString();
        $data['tenant_id'] = $tenantId;

        return Vendor::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $vendorId, array $data): VendorInterface
    {
        $vendor = Vendor::findOrFail($vendorId);
        $vendor->update($data);
        return $vendor->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $vendorId): bool
    {
        return Vendor::destroy($vendorId) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function codeExists(string $tenantId, string $code): bool
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->exists();
    }
}
