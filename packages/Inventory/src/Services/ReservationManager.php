<?php

declare(strict_types=1);

namespace Nexus\Inventory\Services;

use Nexus\Inventory\Contracts\ReservationManagerInterface;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Events\StockReservedEvent;
use Nexus\Inventory\Events\ReservationExpiredEvent;
use Nexus\Inventory\Exceptions\InsufficientStockException;
use Psr\Log\LoggerInterface;

/**
 * Stock reservation management service
 */
final readonly class ReservationManager implements ReservationManagerInterface
{
    public function __construct(
        private ReservationRepositoryInterface $repository,
        private StockLevelRepositoryInterface $stockLevelRepository,
        private EventPublisherInterface $eventPublisher,
        private LoggerInterface $logger
    ) {
    }
    
    public function reserveStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        string $referenceType,
        string $referenceId,
        int $ttlHours = 24
    ): string {
        $this->logger->info('Reserving stock', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'reference' => "{$referenceType}:{$referenceId}",
        ]);
        
        // Check available stock (on-hand minus existing reservations)
        $onHand = $this->stockLevelRepository->getCurrentLevel($productId, $warehouseId);
        $reserved = $this->stockLevelRepository->getReservedQuantity($productId, $warehouseId);
        $available = $onHand - $reserved;
        
        if ($available < $quantity) {
            throw InsufficientStockException::forProduct($productId, $warehouseId, $quantity, $available);
        }
        
        // Create reservation
        $expiresAt = (new \DateTimeImmutable())->modify("+{$ttlHours} hours");
        $reservationId = $this->repository->create(
            $productId,
            $warehouseId,
            $quantity,
            $referenceType,
            $referenceId,
            $expiresAt
        );
        
        // Publish event
        $event = new StockReservedEvent(
            reservationId: $reservationId,
            productId: $productId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            referenceType: $referenceType,
            referenceId: $referenceId,
            reservedUntil: $expiresAt,
            reservedDate: new \DateTimeImmutable()
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Stock reserved', [
            'reservation_id' => $reservationId,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
        
        return $reservationId;
    }
    
    public function releaseReservation(string $reservationId): void
    {
        $reservation = $this->repository->findById($reservationId);
        
        if ($reservation === null) {
            $this->logger->warning('Reservation not found', ['reservation_id' => $reservationId]);
            return;
        }
        
        $this->repository->delete($reservationId);
        
        // Publish event
        $event = new ReservationExpiredEvent(
            reservationId: $reservationId,
            productId: $reservation['product_id'],
            warehouseId: $reservation['warehouse_id'],
            quantity: $reservation['quantity'],
            autoExpired: false,
            expiredDate: new \DateTimeImmutable()
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Reservation released', ['reservation_id' => $reservationId]);
    }
    
    public function fulfillReservation(string $reservationId): void
    {
        $this->repository->markAsFulfilled($reservationId);
        
        $this->logger->info('Reservation fulfilled', ['reservation_id' => $reservationId]);
    }
    
    public function expireReservations(): int
    {
        $expired = $this->repository->findExpired();
        $count = 0;
        
        foreach ($expired as $reservation) {
            $this->repository->delete($reservation['id']);
            
            // Publish event
            $event = new ReservationExpiredEvent(
                reservationId: $reservation['id'],
                productId: $reservation['product_id'],
                warehouseId: $reservation['warehouse_id'],
                quantity: $reservation['quantity'],
                autoExpired: true,
                expiredDate: new \DateTimeImmutable()
            );
            
            $this->eventPublisher->publish($event);
            
            $count++;
        }
        
        $this->logger->info('Reservations expired', ['count' => $count]);
        
        return $count;
    }
}

/**
 * Reservation repository contract
 */
interface ReservationRepositoryInterface
{
    public function create(string $productId, string $warehouseId, float $quantity, string $referenceType, string $referenceId, \DateTimeImmutable $expiresAt): string;
    public function findById(string $reservationId): ?array;
    public function delete(string $reservationId): void;
    public function markAsFulfilled(string $reservationId): void;
    public function findExpired(): array;
}
