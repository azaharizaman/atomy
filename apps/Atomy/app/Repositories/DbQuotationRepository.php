<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Quotation;
use Nexus\Sales\Contracts\QuotationInterface;
use Nexus\Sales\Contracts\QuotationRepositoryInterface;
use Nexus\Sales\Exceptions\DuplicateQuoteNumberException;
use Nexus\Sales\Exceptions\QuotationNotFoundException;

final readonly class DbQuotationRepository implements QuotationRepositoryInterface
{
    public function findById(string $id): QuotationInterface
    {
        $quotation = Quotation::with('lines')->find($id);

        if ($quotation === null) {
            throw QuotationNotFoundException::forId($id);
        }

        return $quotation;
    }

    public function findByNumber(string $tenantId, string $quoteNumber): QuotationInterface
    {
        $quotation = Quotation::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('quote_number', $quoteNumber)
            ->first();

        if ($quotation === null) {
            throw QuotationNotFoundException::forNumber($tenantId, $quoteNumber);
        }

        return $quotation;
    }

    public function findByCustomer(string $tenantId, string $customerId): array
    {
        return Quotation::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderBy('quote_date', 'desc')
            ->get()
            ->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return Quotation::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderBy('quote_date', 'desc')
            ->get()
            ->all();
    }

    public function save(QuotationInterface $quotation): void
    {
        if (!$quotation instanceof Quotation) {
            throw new \InvalidArgumentException('Quotation must be an Eloquent model');
        }

        // Check for duplicate quote number
        if ($this->exists($quotation->getTenantId(), $quotation->getQuoteNumber())) {
            $existing = Quotation::where('tenant_id', $quotation->getTenantId())
                ->where('quote_number', $quotation->getQuoteNumber())
                ->first();

            if ($existing && $existing->id !== $quotation->id) {
                throw DuplicateQuoteNumberException::forNumber(
                    $quotation->getTenantId(),
                    $quotation->getQuoteNumber()
                );
            }
        }

        $quotation->save();
    }

    public function delete(string $id): void
    {
        $quotation = Quotation::find($id);

        if ($quotation !== null) {
            $quotation->delete();
        }
    }

    public function exists(string $tenantId, string $quoteNumber): bool
    {
        return Quotation::where('tenant_id', $tenantId)
            ->where('quote_number', $quoteNumber)
            ->exists();
    }
}
