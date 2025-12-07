<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Payable\Contracts\VendorBillQueryInterface;
use Nexus\CashManagement\Contracts\BankAccountQueryInterface;
use Nexus\Party\Contracts\VendorQueryInterface;
use Nexus\ProcurementOperations\DTOs\PaymentBatchContext;
use Nexus\ProcurementOperations\Exceptions\PaymentException;

/**
 * Aggregates payment data from multiple packages.
 *
 * Fetches vendor bill information along with related data from
 * CashManagement (bank accounts) and Party (vendors).
 */
final readonly class PaymentDataProvider
{
    public function __construct(
        private VendorBillQueryInterface $vendorBillQuery,
        private ?BankAccountQueryInterface $bankAccountQuery = null,
        private ?VendorQueryInterface $vendorQuery = null,
    ) {}

    /**
     * Build payment batch context.
     *
     * @param array<string> $vendorBillIds
     * @throws PaymentException
     */
    public function buildBatchContext(
        string $tenantId,
        string $paymentBatchId,
        array $vendorBillIds,
        string $paymentMethod,
        string $bankAccountId,
        bool $calculateDiscounts = true
    ): PaymentBatchContext {
        // Validate bank account exists
        $bankAccountInfo = null;
        if ($this->bankAccountQuery !== null) {
            $bankAccount = $this->bankAccountQuery->findById($bankAccountId);
            if ($bankAccount === null) {
                throw PaymentException::bankAccountNotFound($bankAccountId);
            }

            $bankAccountInfo = [
                'bankAccountId' => $bankAccount->getId(),
                'bankAccountNumber' => $bankAccount->getAccountNumber(),
                'bankName' => $bankAccount->getBankName(),
                'currency' => $bankAccount->getCurrency(),
                'availableBalanceCents' => $bankAccount->getAvailableBalanceCents(),
            ];
        }

        // Fetch invoices and calculate totals
        $invoices = [];
        $totalAmountCents = 0;
        $totalDiscountCents = 0;
        $vendorTotals = [];

        foreach ($vendorBillIds as $billId) {
            $bill = $this->vendorBillQuery->findById($billId);
            if ($bill === null) {
                continue;
            }

            // Check invoice is ready for payment
            if (!$bill->isMatchedAndApproved()) {
                throw PaymentException::invoiceNotApproved($billId);
            }

            // Calculate early payment discount
            $discountCents = 0;
            if ($calculateDiscounts && $bill->getDiscountDate() !== null) {
                $today = new \DateTimeImmutable('today');
                if ($today <= $bill->getDiscountDate()) {
                    $discountCents = $bill->getDiscountAmountCents();
                }
            }

            $netAmountCents = $bill->getTotalAmountCents() - $discountCents;

            // Get vendor name
            $vendorName = $bill->getVendorName() ?? 'Unknown';
            if ($this->vendorQuery !== null && $vendorName === 'Unknown') {
                $vendor = $this->vendorQuery->findById($bill->getVendorId());
                $vendorName = $vendor?->getName() ?? 'Unknown';
            }

            $invoices[] = [
                'vendorBillId' => $bill->getId(),
                'vendorBillNumber' => $bill->getBillNumber(),
                'vendorId' => $bill->getVendorId(),
                'vendorName' => $vendorName,
                'amountCents' => $bill->getTotalAmountCents(),
                'discountCents' => $discountCents,
                'netAmountCents' => $netAmountCents,
                'dueDate' => $bill->getDueDate(),
                'discountDate' => $bill->getDiscountDate(),
            ];

            $totalAmountCents += $bill->getTotalAmountCents();
            $totalDiscountCents += $discountCents;

            // Aggregate by vendor
            $vendorId = $bill->getVendorId();
            if (!isset($vendorTotals[$vendorId])) {
                $vendorTotals[$vendorId] = [
                    'vendorId' => $vendorId,
                    'vendorName' => $vendorName,
                    'totalAmountCents' => 0,
                    'invoiceCount' => 0,
                ];
            }
            $vendorTotals[$vendorId]['totalAmountCents'] += $netAmountCents;
            $vendorTotals[$vendorId]['invoiceCount']++;
        }

        $netAmountCents = $totalAmountCents - $totalDiscountCents;

        return new PaymentBatchContext(
            tenantId: $tenantId,
            paymentBatchId: $paymentBatchId,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
            totalAmountCents: $totalAmountCents,
            totalDiscountCents: $totalDiscountCents,
            netAmountCents: $netAmountCents,
            currency: $bankAccountInfo['currency'] ?? 'MYR',
            invoices: $invoices,
            bankAccountInfo: $bankAccountInfo,
            vendorSummary: $vendorTotals,
            status: 'pending',
        );
    }

    /**
     * Get payment status for vendor bills.
     *
     * @param array<string> $vendorBillIds
     * @return array<string, array{
     *     status: string,
     *     paymentId: ?string,
     *     scheduledDate: ?\DateTimeImmutable,
     *     executedDate: ?\DateTimeImmutable,
     *     amountCents: int
     * }>
     */
    public function getPaymentStatus(string $tenantId, array $vendorBillIds): array
    {
        $result = [];

        foreach ($vendorBillIds as $billId) {
            $bill = $this->vendorBillQuery->findById($billId);

            if ($bill === null) {
                $result[$billId] = [
                    'status' => 'not_found',
                    'paymentId' => null,
                    'scheduledDate' => null,
                    'executedDate' => null,
                    'amountCents' => 0,
                ];
                continue;
            }

            $paymentInfo = $bill->getPaymentInfo();

            $result[$billId] = [
                'status' => $paymentInfo['status'] ?? 'unpaid',
                'paymentId' => $paymentInfo['paymentId'] ?? null,
                'scheduledDate' => $paymentInfo['scheduledDate'] ?? null,
                'executedDate' => $paymentInfo['executedDate'] ?? null,
                'amountCents' => $bill->getTotalAmountCents(),
            ];
        }

        return $result;
    }

    /**
     * Check for duplicate payments.
     *
     * @return array<string, string> Map of bill ID to existing payment ID
     */
    public function checkDuplicatePayments(array $vendorBillIds): array
    {
        $duplicates = [];

        foreach ($vendorBillIds as $billId) {
            $bill = $this->vendorBillQuery->findById($billId);

            if ($bill !== null && $bill->isPaid()) {
                $paymentInfo = $bill->getPaymentInfo();
                $duplicates[$billId] = $paymentInfo['paymentId'] ?? 'unknown';
            }
        }

        return $duplicates;
    }

    /**
     * Get vendor bills by IDs with minimal data for grouping operations.
     *
     * @param string $tenantId Tenant ID (reserved for future multi-tenant filtering)
     * @param array<string> $vendorBillIds
     * @return array<array{
     *     id: string,
     *     vendorId: string,
     *     vendorCountry: ?string,
     *     tenantCountry: ?string,
     *     vendorPreferredMethod: ?string
     * }>
     */
    public function getBillsByIds(string $tenantId, array $vendorBillIds): array
    {
        $bills = [];

        foreach ($vendorBillIds as $billId) {
            $bill = $this->vendorBillQuery->findById($billId);

            if ($bill === null) {
                continue;
            }

            // Get vendor information for payment method determination
            $vendorCountry = null;
            $vendorPreferredMethod = null;
            if ($this->vendorQuery !== null) {
                $vendor = $this->vendorQuery->findById($bill->getVendorId());
                if ($vendor !== null) {
                    $vendorCountry = $vendor->getCountry();
                    $vendorPreferredMethod = $vendor->getPreferredPaymentMethod();
                }
            }

            $bills[] = [
                'id' => $bill->getId(),
                'vendorId' => $bill->getVendorId(),
                'vendorCountry' => $vendorCountry,
                'tenantCountry' => 'MY', // TODO: Get from tenant configuration by $tenantId
                'vendorPreferredMethod' => $vendorPreferredMethod,
            ];
        }

        return $bills;
    }

    /**
     * Check if auto payment scheduling is enabled for tenant/vendor.
     *
     * @param string $tenantId Tenant ID (will be used for tenant-specific settings query)
     * @param string $vendorId Vendor ID (will be used for vendor-specific configuration)
     */
    public function isAutoPaymentSchedulingEnabled(string $tenantId, string $vendorId): bool
    {
        // TODO: Query tenant settings and vendor-specific configuration
        // For now, return false to prevent unintended auto-scheduling
        return false;
    }

    /**
     * Get default bank account for tenant.
     *
     * @param string $tenantId Tenant ID (will be used to query tenant settings)
     */
    public function getDefaultBankAccount(string $tenantId): ?string
    {
        // TODO: Query tenant settings for default bank account
        // Return null if not configured
        return null;
    }

    /**
     * Get default payment method for tenant.
     *
     * @param string $tenantId Tenant ID (will be used to query tenant settings)
     */
    public function getDefaultPaymentMethod(string $tenantId): string
    {
        // TODO: Query tenant settings by $tenantId for default payment method
        // Return a configurable default per tenant
        return 'bank_transfer';
    }

    /**
     * Get batch data by batch ID.
     *
     * @param string $tenantId Tenant ID (will be used for tenant-scoped batch retrieval)
     * @param string $paymentBatchId Batch ID to retrieve
     * @return array{
     *     vendorBillIds: array<string>,
     *     totalAmountCents: int,
     *     currency: string,
     *     netAmountCents: int,
     *     discountCents: int
     * }|null
     */
    public function getBatchById(string $tenantId, string $paymentBatchId): ?array
    {
        // TODO: Implement batch storage/retrieval
        // This would typically query a payment batch repository
        // For now, return null to indicate batch not found
        return null;
    }
}
