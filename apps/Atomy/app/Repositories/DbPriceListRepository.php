<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PriceList;
use DateTimeImmutable;
use Nexus\Sales\Contracts\PriceListInterface;
use Nexus\Sales\Contracts\PriceListRepositoryInterface;

final readonly class DbPriceListRepository implements PriceListRepositoryInterface
{
    public function findById(string $id): PriceListInterface
    {
        $priceList = PriceList::with(['items.tiers'])->find($id);

        if ($priceList === null) {
            throw new \RuntimeException("Price list with ID '{$id}' not found");
        }

        return $priceList;
    }

    public function findByTenant(string $tenantId): array
    {
        return PriceList::with(['items.tiers'])
            ->where('tenant_id', $tenantId)
            ->orderBy('valid_from', 'desc')
            ->get()
            ->all();
    }

    public function findActiveByCustomer(string $tenantId, string $customerId, DateTimeImmutable $asOf): array
    {
        $now = \DateTime::createFromImmutable($asOf);

        return PriceList::with(['items.tiers'])
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->where('is_active', true)
            ->where('valid_from', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            })
            ->orderBy('valid_from', 'desc')
            ->get()
            ->all();
    }

    public function findDefaultActive(string $tenantId, DateTimeImmutable $asOf): array
    {
        $now = \DateTime::createFromImmutable($asOf);

        return PriceList::with(['items.tiers'])
            ->where('tenant_id', $tenantId)
            ->whereNull('customer_id')
            ->where('is_active', true)
            ->where('valid_from', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            })
            ->orderBy('valid_from', 'desc')
            ->get()
            ->all();
    }

    public function save(PriceListInterface $priceList): void
    {
        if (!$priceList instanceof PriceList) {
            throw new \InvalidArgumentException('PriceList must be an Eloquent model');
        }

        $priceList->save();
    }

    public function delete(string $id): void
    {
        $priceList = PriceList::find($id);

        if ($priceList !== null) {
            $priceList->delete();
        }
    }
}
