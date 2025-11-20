<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SalesOrder;
use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Exceptions\DuplicateOrderNumberException;
use Nexus\Sales\Exceptions\SalesOrderNotFoundException;

final readonly class DbSalesOrderRepository implements SalesOrderRepositoryInterface
{
    public function findById(string $id): SalesOrderInterface
    {
        $order = SalesOrder::with('lines')->find($id);

        if ($order === null) {
            throw SalesOrderNotFoundException::forId($id);
        }

        return $order;
    }

    public function findByNumber(string $tenantId, string $orderNumber): SalesOrderInterface
    {
        $order = SalesOrder::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('order_number', $orderNumber)
            ->first();

        if ($order === null) {
            throw SalesOrderNotFoundException::forNumber($tenantId, $orderNumber);
        }

        return $order;
    }

    public function findByCustomer(string $tenantId, string $customerId): array
    {
        return SalesOrder::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderBy('order_date', 'desc')
            ->get()
            ->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return SalesOrder::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderBy('order_date', 'desc')
            ->get()
            ->all();
    }

    public function save(SalesOrderInterface $order): void
    {
        if (!$order instanceof SalesOrder) {
            throw new \InvalidArgumentException('SalesOrder must be an Eloquent model');
        }

        // Check for duplicate order number
        if ($this->exists($order->getTenantId(), $order->getOrderNumber())) {
            $existing = SalesOrder::where('tenant_id', $order->getTenantId())
                ->where('order_number', $order->getOrderNumber())
                ->first();

            if ($existing && $existing->id !== $order->id) {
                throw DuplicateOrderNumberException::forNumber(
                    $order->getTenantId(),
                    $order->getOrderNumber()
                );
            }
        }

        $order->save();
    }

    public function delete(string $id): void
    {
        $order = SalesOrder::find($id);

        if ($order !== null) {
            $order->delete();
        }
    }

    public function exists(string $tenantId, string $orderNumber): bool
    {
        return SalesOrder::where('tenant_id', $tenantId)
            ->where('order_number', $orderNumber)
            ->exists();
    }
}
