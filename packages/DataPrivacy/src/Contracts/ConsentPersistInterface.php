<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\Consent;

/**
 * Write operations for consent records (CQRS Command Model).
 */
interface ConsentPersistInterface
{
    /**
     * Save a new consent record.
     *
     * @return string The generated consent ID
     */
    public function save(Consent $consent): string;

    /**
     * Update an existing consent record.
     */
    public function update(Consent $consent): void;

    /**
     * Delete a consent record.
     */
    public function delete(string $id): void;

    /**
     * Withdraw consent and update record.
     *
     * @return Consent The updated consent with withdrawn status
     */
    public function withdraw(string $id): Consent;

    /**
     * Mark consent as expired.
     *
     * @return Consent The updated consent with expired status
     */
    public function markExpired(string $id): Consent;

    /**
     * Bulk mark expired consents.
     *
     * @return int Number of consents marked as expired
     */
    public function bulkMarkExpired(): int;
}
