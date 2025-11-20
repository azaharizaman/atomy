<?php

declare(strict_types=1);

namespace Nexus\Payable\Services;

use Nexus\Payable\Contracts\VendorRepositoryInterface;
use Nexus\Payable\Contracts\VendorInterface;
use Nexus\Payable\Enums\VendorStatus;
use Nexus\Payable\Exceptions\DuplicateVendorException;
use Nexus\Payable\Exceptions\VendorNotFoundException;
use Nexus\AuditLogger\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

/**
 * Vendor management service.
 */
final class VendorManager
{
    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Create a new vendor.
     *
     * @param string $tenantId
     * @param array $data Vendor data
     * @return VendorInterface
     * @throws DuplicateVendorException
     */
    public function createVendor(string $tenantId, array $data): VendorInterface
    {
        // Validate unique code
        if ($this->vendorRepository->codeExists($tenantId, $data['code'])) {
            throw DuplicateVendorException::forCode($data['code']);
        }

        // Validate unique tax ID if provided
        if (!empty($data['tax_id']) && $this->vendorRepository->findByTaxId($tenantId, $data['tax_id'])) {
            throw DuplicateVendorException::forTaxId($data['tax_id']);
        }

        // Set defaults
        $data['status'] = $data['status'] ?? VendorStatus::ACTIVE->value;
        $data['qty_tolerance_percent'] = $data['qty_tolerance_percent'] ?? 5.0;
        $data['price_tolerance_percent'] = $data['price_tolerance_percent'] ?? 2.0;

        $vendor = $this->vendorRepository->create($tenantId, $data);

        $this->auditLogger->log(
            entity: 'vendor',
            entityId: $vendor->getId(),
            action: 'created',
            tenantId: $tenantId,
            changes: $data
        );

        $this->logger->info("Vendor created: {$vendor->getCode()} ({$vendor->getId()})");

        return $vendor;
    }

    /**
     * Update vendor.
     *
     * @param string $vendorId
     * @param array $data
     * @return VendorInterface
     * @throws VendorNotFoundException
     */
    public function updateVendor(string $vendorId, array $data): VendorInterface
    {
        $vendor = $this->vendorRepository->findById($vendorId);
        if (!$vendor) {
            throw VendorNotFoundException::forId($vendorId);
        }

        // Check code uniqueness if changed
        if (isset($data['code']) && $data['code'] !== $vendor->getCode()) {
            if ($this->vendorRepository->codeExists($vendor->getTenantId(), $data['code'])) {
                throw DuplicateVendorException::forCode($data['code']);
            }
        }

        $updatedVendor = $this->vendorRepository->update($vendorId, $data);

        $this->auditLogger->log(
            entity: 'vendor',
            entityId: $vendorId,
            action: 'updated',
            tenantId: $vendor->getTenantId(),
            changes: $data
        );

        $this->logger->info("Vendor updated: {$vendorId}");

        return $updatedVendor;
    }

    /**
     * Activate vendor.
     *
     * @param string $vendorId
     * @return VendorInterface
     */
    public function activateVendor(string $vendorId): VendorInterface
    {
        return $this->updateVendor($vendorId, ['status' => VendorStatus::ACTIVE->value]);
    }

    /**
     * Deactivate vendor.
     *
     * @param string $vendorId
     * @return VendorInterface
     */
    public function deactivateVendor(string $vendorId): VendorInterface
    {
        return $this->updateVendor($vendorId, ['status' => VendorStatus::INACTIVE->value]);
    }

    /**
     * Block vendor.
     *
     * @param string $vendorId
     * @param string $reason
     * @return VendorInterface
     */
    public function blockVendor(string $vendorId, string $reason): VendorInterface
    {
        $vendor = $this->updateVendor($vendorId, ['status' => VendorStatus::BLOCKED->value]);

        $this->auditLogger->log(
            entity: 'vendor',
            entityId: $vendorId,
            action: 'blocked',
            tenantId: $vendor->getTenantId(),
            metadata: ['reason' => $reason]
        );

        return $vendor;
    }

    /**
     * Get vendor by ID.
     *
     * @param string $vendorId
     * @return VendorInterface
     * @throws VendorNotFoundException
     */
    public function getVendor(string $vendorId): VendorInterface
    {
        $vendor = $this->vendorRepository->findById($vendorId);
        if (!$vendor) {
            throw VendorNotFoundException::forId($vendorId);
        }

        return $vendor;
    }

    /**
     * Get vendor by code.
     *
     * @param string $tenantId
     * @param string $code
     * @return VendorInterface
     * @throws VendorNotFoundException
     */
    public function getVendorByCode(string $tenantId, string $code): VendorInterface
    {
        $vendor = $this->vendorRepository->findByCode($tenantId, $code);
        if (!$vendor) {
            throw VendorNotFoundException::forCode($code);
        }

        return $vendor;
    }

    /**
     * List all vendors for tenant.
     *
     * @param string $tenantId
     * @param array $filters
     * @return array<VendorInterface>
     */
    public function listVendors(string $tenantId, array $filters = []): array
    {
        return $this->vendorRepository->getAll($tenantId, $filters);
    }
}
