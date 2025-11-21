<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UnappliedCash;
use Nexus\Receivable\Contracts\UnappliedCashInterface;
use Nexus\Receivable\Contracts\UnappliedCashRepositoryInterface;

/**
 * Eloquent Unapplied Cash Repository
 */
final readonly class EloquentUnappliedCashRepository implements UnappliedCashRepositoryInterface
{
    public function findById(string $id): ?UnappliedCashInterface
    {
        return UnappliedCash::find($id);
    }

    public function getById(string $id): UnappliedCashInterface
    {
        $unappliedCash = $this->findById($id);

        if ($unappliedCash === null) {
            throw new \RuntimeException("Unapplied cash record {$id} not found");
        }

        return $unappliedCash;
    }

    /**
     * @return UnappliedCashInterface[]
     */
    public function getByCustomer(string $customerId): array
    {
        return UnappliedCash::where('customer_id', $customerId)
            ->orderBy('receipt_date')
            ->get()
            ->all();
    }

    public function getTotalUnappliedCash(string $customerId): float
    {
        return UnappliedCash::where('customer_id', $customerId)
            ->sum('amount');
    }

    public function save(UnappliedCashInterface $unappliedCash): void
    {
        if (!$unappliedCash instanceof UnappliedCash) {
            throw new \InvalidArgumentException('Unapplied cash must be an Eloquent model');
        }

        $unappliedCash->save();
    }

    public function delete(string $id): void
    {
        $unappliedCash = $this->getById($id);

        if (!$unappliedCash instanceof UnappliedCash) {
            throw new \InvalidArgumentException('Unapplied cash must be an Eloquent model');
        }

        $unappliedCash->delete();
    }
}
