<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Repository interface for payment persistence operations.
 */
interface PaymentRepositoryInterface
{
    /**
     * Find payment by ID.
     *
     * @param string $id Payment ULID
     * @return PaymentInterface|null
     */
    public function findById(string $id): ?PaymentInterface;

    /**
     * Find payment by payment number.
     *
     * @param string $tenantId Tenant ULID
     * @param string $paymentNumber Payment number
     * @return PaymentInterface|null
     */
    public function findByPaymentNumber(string $tenantId, string $paymentNumber): ?PaymentInterface;

    /**
     * Get payments for a vendor.
     *
     * @param string $tenantId Tenant ULID
     * @param string $vendorId Vendor ULID
     * @param array $filters Optional filters (status, date_range)
     * @return array<PaymentInterface>
     */
    public function getByVendor(string $tenantId, string $vendorId, array $filters = []): array;

    /**
     * Get payments by status.
     *
     * @param string $tenantId Tenant ULID
     * @param string $status Payment status
     * @return array<PaymentInterface>
     */
    public function getByStatus(string $tenantId, string $status): array;

    /**
     * Create a new payment.
     *
     * @param string $tenantId Tenant ULID
     * @param array $data Payment data
     * @return PaymentInterface
     */
    public function create(string $tenantId, array $data): PaymentInterface;

    /**
     * Update payment.
     *
     * @param string $id Payment ULID
     * @param array $data Updated payment data
     * @return PaymentInterface
     */
    public function update(string $id, array $data): PaymentInterface;

    /**
     * Delete payment.
     *
     * @param string $id Payment ULID
     * @return bool True if deleted, false otherwise
     */
    public function delete(string $id): bool;

    /**
     * Check if payment number exists.
     *
     * @param string $tenantId Tenant ULID
     * @param string $paymentNumber Payment number
     * @return bool
     */
    public function paymentNumberExists(string $tenantId, string $paymentNumber): bool;
}
