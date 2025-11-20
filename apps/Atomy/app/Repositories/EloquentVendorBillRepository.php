<?php

declare(strict_types=1);

namespace Atomy\Repositories;

use Atomy\Models\VendorBill;
use Atomy\Models\VendorBillLine;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\Payable\Contracts\VendorBillInterface;
use Illuminate\Support\Str;

/**
 * Eloquent vendor bill repository implementation.
 */
final class EloquentVendorBillRepository implements VendorBillRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findById(string $billId): ?VendorBillInterface
    {
        return VendorBill::with('lines')->find($billId);
    }

    /**
     * {@inheritdoc}
     */
    public function findByBillNumber(string $tenantId, string $vendorId, string $billNumber): ?VendorBillInterface
    {
        return VendorBill::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->where('bill_number', $billNumber)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByVendor(string $tenantId, string $vendorId, array $filters = []): array
    {
        $query = VendorBill::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['matching_status'])) {
            $query->where('matching_status', $filters['matching_status']);
        }

        return $query->orderBy('bill_date', 'desc')->get()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingMatching(string $tenantId): array
    {
        return VendorBill::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('matching_status', 'pending')
            ->orderBy('bill_date')
            ->get()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getReadyForPosting(string $tenantId): array
    {
        return VendorBill::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereNull('gl_journal_id')
            ->orderBy('bill_date')
            ->get()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getForAgingReport(string $tenantId, ?\DateTimeInterface $asOfDate = null): array
    {
        $query = VendorBill::with('lines')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['posted', 'partially_paid']);

        if ($asOfDate) {
            $query->where('bill_date', '<=', $asOfDate->format('Y-m-d'));
        }

        return $query->orderBy('due_date')->get()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $tenantId, array $data): VendorBillInterface
    {
        $billId = Str::uuid()->toString();
        $data['id'] = $billId;
        $data['tenant_id'] = $tenantId;

        // Extract lines
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        // Create bill
        $bill = VendorBill::create($data);

        // Create lines
        foreach ($lines as $idx => $lineData) {
            VendorBillLine::create([
                'id' => Str::uuid()->toString(),
                'bill_id' => $billId,
                'line_number' => $idx + 1,
                'description' => $lineData['description'],
                'quantity' => $lineData['quantity'],
                'unit_price' => $lineData['unit_price'],
                'line_amount' => $lineData['quantity'] * $lineData['unit_price'],
                'gl_account' => $lineData['gl_account'],
                'tax_code' => $lineData['tax_code'] ?? null,
                'po_line_reference' => $lineData['po_line_reference'] ?? null,
                'grn_line_reference' => $lineData['grn_line_reference'] ?? null,
            ]);
        }

        return VendorBill::with('lines')->find($billId);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $billId, array $data): VendorBillInterface
    {
        $bill = VendorBill::findOrFail($billId);

        // Remove lines from update data (lines are immutable after creation in this implementation)
        unset($data['lines']);

        $bill->update($data);
        return VendorBill::with('lines')->find($billId);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $billId): bool
    {
        return VendorBill::destroy($billId) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function billNumberExists(string $tenantId, string $vendorId, string $billNumber): bool
    {
        return VendorBill::where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->where('bill_number', $billNumber)
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalOutstanding(string $tenantId, string $vendorId): float
    {
        return VendorBill::where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('total_amount');
    }
}
