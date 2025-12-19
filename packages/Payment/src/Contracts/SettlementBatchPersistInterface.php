<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

/**
 * Settlement Batch Persist Interface
 *
 * Defines write operations for settlement batches (CQRS Command side).
 *
 * @package Nexus\Payment\Contracts
 */
interface SettlementBatchPersistInterface
{
    /**
     * Save a settlement batch (create or update).
     *
     * @param SettlementBatchInterface $batch Batch to save
     * @return SettlementBatchInterface Saved batch
     */
    public function save(SettlementBatchInterface $batch): SettlementBatchInterface;

    /**
     * Delete a settlement batch.
     *
     * @param string $id Batch ID
     * @throws \Nexus\Payment\Exceptions\SettlementBatchNotFoundException If not found
     */
    public function delete(string $id): void;
}
