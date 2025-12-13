<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Settlement status in intercompany workflow.
 */
enum SettlementStatus: string
{
    case PENDING_NETTING = 'pending_netting';
    case NETTING_CALCULATED = 'netting_calculated';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case PENDING_PAYMENT = 'pending_payment';
    case SETTLED = 'settled';
    case PARTIALLY_SETTLED = 'partially_settled';
    case CANCELLED = 'cancelled';
    case DISPUTED = 'disputed';

    /**
     * Check if settlement is in a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::SETTLED,
            self::CANCELLED,
        ], true);
    }

    /**
     * Check if settlement can be modified.
     */
    public function isModifiable(): bool
    {
        return in_array($this, [
            self::PENDING_NETTING,
            self::NETTING_CALCULATED,
            self::DISPUTED,
        ], true);
    }

    /**
     * Check if settlement requires payment.
     */
    public function requiresPayment(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::PENDING_PAYMENT,
        ], true);
    }

    /**
     * Get allowed next statuses.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING_NETTING => [self::NETTING_CALCULATED, self::CANCELLED],
            self::NETTING_CALCULATED => [self::PENDING_APPROVAL, self::PENDING_NETTING, self::CANCELLED],
            self::PENDING_APPROVAL => [self::APPROVED, self::NETTING_CALCULATED, self::CANCELLED, self::DISPUTED],
            self::APPROVED => [self::PENDING_PAYMENT, self::CANCELLED],
            self::PENDING_PAYMENT => [self::SETTLED, self::PARTIALLY_SETTLED, self::CANCELLED],
            self::PARTIALLY_SETTLED => [self::SETTLED, self::PENDING_PAYMENT, self::CANCELLED],
            self::SETTLED => [],
            self::CANCELLED => [],
            self::DISPUTED => [self::PENDING_NETTING, self::CANCELLED],
        };
    }

    /**
     * Check if transition to new status is allowed.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
}
