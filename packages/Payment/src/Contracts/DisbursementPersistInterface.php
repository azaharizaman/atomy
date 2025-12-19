<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

/**
 * Persist interface for disbursements (CQRS - Write).
 */
interface DisbursementPersistInterface
{
    /**
     * Save a disbursement (create or update).
     */
    public function save(DisbursementInterface $disbursement): DisbursementInterface;

    /**
     * Delete a disbursement (only allowed for drafts).
     */
    public function delete(string $id): void;

    /**
     * Link a payment transaction to a disbursement.
     */
    public function linkPaymentTransaction(string $disbursementId, string $paymentTransactionId): void;
}
