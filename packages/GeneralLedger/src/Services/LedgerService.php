<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Services;

use Nexus\GeneralLedger\Contracts\IdGeneratorInterface;
use Nexus\GeneralLedger\Contracts\LedgerQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerPersistInterface;
use Nexus\GeneralLedger\Entities\Ledger;
use Nexus\GeneralLedger\Enums\LedgerStatus;
use Nexus\GeneralLedger\Enums\LedgerType;
use Nexus\GeneralLedger\Exceptions\LedgerAlreadyActiveException;
use Nexus\GeneralLedger\Exceptions\LedgerAlreadyClosedException;
use Nexus\GeneralLedger\Exceptions\LedgerNotFoundException;

/**
 * Ledger Service
 * 
 * Main service for ledger operations including creation, retrieval,
 * and status management.
 */
final readonly class LedgerService
{
    public function __construct(
        private LedgerQueryInterface $queryRepository,
        private LedgerPersistInterface $persistRepository,
        private IdGeneratorInterface $idGenerator,
    ) {}

    /**
     * Create a new ledger
     * 
     * @param string $tenantId Tenant ULID
     * @param string $name Ledger name
     * @param LedgerType $type Ledger type
     * @param string $currency ISO currency code
     * @param string|null $description Optional description
     * @return Ledger Created ledger
     */
    public function createLedger(
        string $tenantId,
        string $name,
        LedgerType $type,
        string $currency,
        ?string $description = null,
    ): Ledger {
        // Validate currency format (ISO 4217 - 3 uppercase letters)
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid currency code: %s. Expected ISO 4217 format (e.g., USD, EUR)', $currency)
            );
        }

        $ledger = Ledger::create(
            id: $this->idGenerator->generate(),
            tenantId: $tenantId,
            name: $name,
            currency: $currency,
            type: $type,
            description: $description,
        );

        $this->persistRepository->save($ledger);

        return $ledger;
    }

    /**
     * Get a ledger by ID
     * 
     * @param string $ledgerId Ledger ULID
     * @return Ledger The ledger
     * @throws LedgerNotFoundException If ledger not found
     */
    public function getLedger(string $ledgerId): Ledger
    {
        $ledger = $this->queryRepository->findById($ledgerId);

        if ($ledger === null) {
            throw new LedgerNotFoundException($ledgerId);
        }

        return $ledger;
    }

    /**
     * Get all ledgers for a tenant
     * 
     * @param string $tenantId Tenant ULID
     * @return array<Ledger> Ledgers
     */
    public function getLedgersByTenant(string $tenantId): array
    {
        return $this->queryRepository->findByTenant($tenantId);
    }

    /**
     * Get active ledgers for a tenant
     * 
     * @param string $tenantId Tenant ULID
     * @return array<Ledger> Active ledgers
     */
    public function getActiveLedgers(string $tenantId): array
    {
        return $this->queryRepository->findActiveByTenant($tenantId);
    }

    /**
     * Get ledgers by type
     * 
     * @param string $tenantId Tenant ULID
     * @param LedgerType $type Ledger type
     * @return array<Ledger> Ledgers of the specified type
     */
    public function getLedgersByType(string $tenantId, LedgerType $type): array
    {
        return $this->queryRepository->findByType($tenantId, $type);
    }

    /**
     * Close a ledger
     * 
     * Once closed, no more transactions can be posted.
     * 
     * @param string $ledgerId Ledger ULID
     * @return Ledger Updated ledger
     * @throws LedgerNotFoundException If ledger not found
     */
    public function closeLedger(string $ledgerId): Ledger
    {
        $ledger = $this->getLedger($ledgerId);

        $closedLedger = $ledger->close();
        $this->persistRepository->save($closedLedger);

        return $closedLedger;
    }

    /**
     * Archive a ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @return Ledger Updated ledger
     * @throws LedgerNotFoundException If ledger not found
     */
    public function archiveLedger(string $ledgerId): Ledger
    {
        $ledger = $this->getLedger($ledgerId);

        $archivedLedger = $ledger->archive();
        $this->persistRepository->save($archivedLedger);

        return $archivedLedger;
    }

    /**
     * Reactivate a closed ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @return Ledger Updated ledger
     * @throws LedgerNotFoundException If ledger not found
     */
    public function reactivateLedger(string $ledgerId): Ledger
    {
        $ledger = $this->getLedger($ledgerId);

        $reactivatedLedger = $ledger->reactivate();
        $this->persistRepository->save($reactivatedLedger);

        return $reactivatedLedger;
    }

    /**
     * Check if a ledger can accept transactions
     * 
     * @param string $ledgerId Ledger ULID
     * @return bool True if can post
     */
    public function canPostTransactions(string $ledgerId): bool
    {
        $ledger = $this->queryRepository->findById($ledgerId);
        
        return $ledger?->canPostTransactions() ?? false;
    }
}
