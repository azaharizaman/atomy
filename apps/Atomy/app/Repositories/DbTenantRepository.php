<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantRepositoryInterface;

/**
 * Database Tenant Repository
 *
 * Eloquent implementation of TenantRepositoryInterface.
 */
class DbTenantRepository implements TenantRepositoryInterface
{
    public function findById(string $id): ?TenantInterface
    {
        return Tenant::find($id);
    }

    public function findByCode(string $code): ?TenantInterface
    {
        return Tenant::where('code', $code)->first();
    }

    public function findByDomain(string $domain): ?TenantInterface
    {
        return Tenant::where('domain', $domain)->first();
    }

    public function findBySubdomain(string $subdomain): ?TenantInterface
    {
        return Tenant::where('subdomain', $subdomain)->first();
    }

    public function all(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = Tenant::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('code', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $result->items(),
            'total' => $result->total(),
            'page' => $result->currentPage(),
            'perPage' => $result->perPage(),
        ];
    }

    public function create(array $data): TenantInterface
    {
        return Tenant::create($data);
    }

    public function update(string $id, array $data): TenantInterface
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update($data);
        return $tenant->fresh();
    }

    public function delete(string $id): bool
    {
        $tenant = Tenant::findOrFail($id);
        return $tenant->delete();
    }

    public function forceDelete(string $id): bool
    {
        $tenant = Tenant::withTrashed()->findOrFail($id);
        return $tenant->forceDelete();
    }

    public function restore(string $id): TenantInterface
    {
        $tenant = Tenant::withTrashed()->findOrFail($id);
        $tenant->restore();
        return $tenant;
    }

    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $query = Tenant::where('code', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function domainExists(string $domain, ?string $excludeId = null): bool
    {
        $query = Tenant::where('domain', $domain);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function getActive(): array
    {
        return Tenant::where('status', 'active')->get()->all();
    }

    public function getSuspended(): array
    {
        return Tenant::where('status', 'suspended')->get()->all();
    }

    public function getTrials(): array
    {
        return Tenant::where('status', 'trial')->get()->all();
    }

    public function getExpiredTrials(): array
    {
        return Tenant::where('status', 'trial')
            ->where('trial_ends_at', '<', now())
            ->get()
            ->all();
    }

    public function getChildren(string $parentId): array
    {
        return Tenant::where('parent_id', $parentId)->get()->all();
    }

    public function getStatistics(): array
    {
        return [
            'total' => Tenant::count(),
            'active' => Tenant::where('status', 'active')->count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
            'trial' => Tenant::where('status', 'trial')->count(),
            'archived' => Tenant::onlyTrashed()->count(),
        ];
    }

    public function search(string $query, int $limit = 10): array
    {
        return Tenant::where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit($limit)
            ->get()
            ->all();
    }
}
