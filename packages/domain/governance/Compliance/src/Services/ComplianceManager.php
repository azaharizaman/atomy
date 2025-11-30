<?php

declare(strict_types=1);

namespace Nexus\Compliance\Services;

use Nexus\Compliance\Contracts\ComplianceManagerInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeRepositoryInterface;
use Nexus\Compliance\Exceptions\InvalidSchemeException;
use Nexus\Compliance\Exceptions\SchemeAlreadyActiveException;
use Nexus\Compliance\Exceptions\SchemeNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Service for managing compliance schemes.
 */
final class ComplianceManager implements ComplianceManagerInterface
{
    /**
     * List of supported compliance schemes.
     */
    private const SUPPORTED_SCHEMES = [
        'ISO14001',
        'SOX',
        'GDPR',
        'HIPAA',
        'PCI_DSS',
    ];

    public function __construct(
        private readonly ComplianceSchemeRepositoryInterface $repository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function activateScheme(string $tenantId, string $schemeName, array $configuration = []): string
    {
        $this->logger->info("Activating compliance scheme", [
            'tenant_id' => $tenantId,
            'scheme_name' => $schemeName,
        ]);

        // Validate scheme name
        if (!in_array($schemeName, self::SUPPORTED_SCHEMES, true)) {
            throw new InvalidSchemeException($schemeName);
        }

        // Check if already active
        $existing = $this->repository->findByTenantAndName($tenantId, $schemeName);
        if ($existing !== null && $existing->isActive()) {
            throw new SchemeAlreadyActiveException($schemeName, $tenantId);
        }

        // Create new scheme activation
        // This is a skeleton - actual entity creation will be done in Atomy layer
        // For now, we just validate the logic
        
        $this->logger->info("Compliance scheme activated successfully", [
            'tenant_id' => $tenantId,
            'scheme_name' => $schemeName,
        ]);

        return 'scheme-id-placeholder';
    }

    public function deactivateScheme(string $tenantId, string $schemeName): void
    {
        $this->logger->info("Deactivating compliance scheme", [
            'tenant_id' => $tenantId,
            'scheme_name' => $schemeName,
        ]);

        $scheme = $this->repository->findByTenantAndName($tenantId, $schemeName);
        if ($scheme === null) {
            throw new SchemeNotFoundException("{$tenantId}:{$schemeName}");
        }

        // Deactivation logic will be implemented in application layer
        
        $this->logger->info("Compliance scheme deactivated successfully", [
            'tenant_id' => $tenantId,
            'scheme_name' => $schemeName,
        ]);
    }

    public function isSchemeActive(string $tenantId, string $schemeName): bool
    {
        $scheme = $this->repository->findByTenantAndName($tenantId, $schemeName);
        return $scheme !== null && $scheme->isActive();
    }

    public function getActiveSchemes(string $tenantId): array
    {
        return $this->repository->getActiveSchemes($tenantId);
    }

    public function updateSchemeConfiguration(string $tenantId, string $schemeName, array $configuration): void
    {
        $this->logger->info("Updating compliance scheme configuration", [
            'tenant_id' => $tenantId,
            'scheme_name' => $schemeName,
        ]);

        $scheme = $this->repository->findByTenantAndName($tenantId, $schemeName);
        if ($scheme === null) {
            throw new SchemeNotFoundException("{$tenantId}:{$schemeName}");
        }

        // Configuration update logic will be implemented in application layer
        
        $this->logger->info("Compliance scheme configuration updated successfully", [
            'tenant_id' => $tenantId,
            'scheme_name' => $schemeName,
        ]);
    }

    public function validateSchemeRequirements(string $tenantId, string $schemeName): array
    {
        $this->logger->info("Validating compliance scheme requirements", [
            'tenant_id' => $tenantId,
            'scheme_name' => $schemeName,
        ]);

        // Validation logic will be implemented in Core/Engine layer
        // This is a placeholder for the service skeleton
        
        return [];
    }
}
