<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Main orchestration interface for accounts payable operations.
 *
 * Provides high-level API for vendor management, bill processing,
 * 3-way matching, payment scheduling, and GL integration.
 */
interface PayableManagerInterface
{
    /**
     * Create a new vendor.
     *
     * @param array $data Vendor data
     * @return VendorInterface
     */
    public function createVendor(array $data): VendorInterface;

    /**
     * Update vendor information.
     *
     * @param string $vendorId Vendor ULID
     * @param array $data Updated vendor data
     * @return VendorInterface
     */
    public function updateVendor(string $vendorId, array $data): VendorInterface;

    /**
     * Find vendor by ID.
     *
     * @param string $vendorId Vendor ULID
     * @return VendorInterface|null
     */
    public function findVendor(string $vendorId): ?VendorInterface;

    /**
     * Find vendor by code.
     *
     * @param string $tenantId Tenant ULID
     * @param string $code Vendor code
     * @return VendorInterface|null
     */
    public function findVendorByCode(string $tenantId, string $code): ?VendorInterface;

    /**
     * Submit vendor bill for processing.
     *
     * @param array $data Bill data including lines
     * @return VendorBillInterface
     */
    public function submitBill(array $data): VendorBillInterface;

    /**
     * Perform 3-way matching on a bill.
     *
     * Validates bill against PO and GRN with vendor-specific tolerances.
     *
     * @param string $billId Bill ULID
     * @return MatchingResultInterface
     */
    public function matchBill(string $billId): MatchingResultInterface;

    /**
     * Post matched bill to general ledger.
     *
     * @param string $billId Bill ULID
     * @return string GL journal entry ID
     * @throws \Nexus\Payable\Exceptions\BillNotFoundException
     * @throws \Nexus\Payable\Exceptions\MatchingToleranceExceededException
     */
    public function postBillToGL(string $billId): string;

    /**
     * Schedule payment for a posted bill.
     *
     * @param string $billId Bill ULID
     * @param array $options Payment options
     * @return PaymentScheduleInterface
     */
    public function schedulePayment(string $billId, array $options = []): PaymentScheduleInterface;

    /**
     * Process payment and post to GL.
     *
     * @param string $scheduleId Payment schedule ULID
     * @param array $data Payment data
     * @return PaymentInterface
     */
    public function processPayment(string $scheduleId, array $data): PaymentInterface;

    /**
     * Get vendor bills with filters.
     *
     * @param string $tenantId Tenant ULID
     * @param array $filters Optional filters (vendor_id, status, date_range)
     * @return array<VendorBillInterface>
     */
    public function getBills(string $tenantId, array $filters = []): array;

    /**
     * Get payment schedules with filters.
     *
     * @param string $tenantId Tenant ULID
     * @param array $filters Optional filters (vendor_id, status, due_date_range)
     * @return array<PaymentScheduleInterface>
     */
    public function getPaymentSchedules(string $tenantId, array $filters = []): array;

    /**
     * Generate vendor aging report.
     *
     * @param string $tenantId Tenant ULID
     * @param \DateTimeInterface $asOfDate As-of date for aging
     * @return array Aging buckets (0-30, 31-60, 61-90, 90+)
     */
    public function getVendorAging(string $tenantId, \DateTimeInterface $asOfDate): array;
}
