<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ComplianceScheme;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeRepositoryInterface;
use Nexus\Compliance\Exceptions\SchemeNotFoundException;

/**
 * Database implementation of ComplianceSchemeRepositoryInterface.
 */
final class DbComplianceSchemeRepository implements ComplianceSchemeRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?ComplianceSchemeInterface
    {
        return ComplianceScheme::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByName(string $tenantId, string $schemeName): ?ComplianceSchemeInterface
    {
        return ComplianceScheme::where('tenant_id', $tenantId)
            ->where('scheme_name', $schemeName)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveSchemes(string $tenantId): array
    {
        return ComplianceScheme::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllSchemes(string $tenantId): array
    {
        return ComplianceScheme::where('tenant_id', $tenantId)
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function save(ComplianceSchemeInterface $scheme): void
    {
        if (!$scheme instanceof ComplianceScheme) {
            throw new \InvalidArgumentException('Scheme must be an instance of ComplianceScheme model');
        }

        $scheme->save();
    }

    /**
     * {@inheritDoc}
     */
    public function create(
        string $tenantId,
        string $schemeName,
        string $description,
        array $configuration
    ): ComplianceSchemeInterface {
        return ComplianceScheme::create([
            'tenant_id' => $tenantId,
            'scheme_name' => $schemeName,
            'description' => $description,
            'configuration' => $configuration,
            'is_active' => false,
            'activated_at' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): void
    {
        $scheme = ComplianceScheme::find($id);
        
        if ($scheme === null) {
            throw new SchemeNotFoundException($id);
        }

        $scheme->delete();
    }
}
