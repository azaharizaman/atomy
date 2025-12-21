<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\DTOs\VirtualCardRequest;
use Nexus\PaymentRails\DTOs\VirtualCardResult;
use Nexus\PaymentRails\ValueObjects\VirtualCard;

/**
 * Contract for Virtual Card rail operations.
 *
 * Virtual cards are single-use or limited-use credit card numbers
 * generated for specific vendor payments, providing enhanced security
 * and control over B2B payments.
 */
interface VirtualCardRailInterface extends PaymentRailInterface
{
    /**
     * Create a new virtual card.
     */
    public function createCard(VirtualCardRequest $request): VirtualCardResult;

    /**
     * Get a virtual card by its identifier.
     */
    public function getCard(string $cardId): ?VirtualCard;

    /**
     * Get a virtual card by its card number.
     */
    public function getCardByNumber(string $cardNumber): ?VirtualCard;

    /**
     * Close a virtual card.
     */
    public function closeCard(string $cardId, string $reason): VirtualCardResult;

    /**
     * Suspend a virtual card temporarily.
     */
    public function suspendCard(string $cardId): VirtualCardResult;

    /**
     * Reactivate a suspended virtual card.
     */
    public function reactivateCard(string $cardId): VirtualCardResult;

    /**
     * Update the credit limit on a card.
     */
    public function updateCreditLimit(string $cardId, Money $newLimit): VirtualCardResult;

    /**
     * Record a charge on the card.
     */
    public function recordCharge(
        string $cardId,
        Money $amount,
        string $merchantName,
        ?string $merchantId = null,
    ): VirtualCardResult;

    /**
     * Get cards associated with a vendor.
     *
     * @return array<VirtualCard>
     */
    public function getCardsByVendor(string $vendorId): array;

    /**
     * Get active cards nearing expiration.
     *
     * @param int $daysUntilExpiration
     * @return array<VirtualCard>
     */
    public function getCardsNearingExpiration(int $daysUntilExpiration = 7): array;

    /**
     * Get cards with available credit.
     *
     * @return array<VirtualCard>
     */
    public function getCardsWithAvailableCredit(): array;

    /**
     * Send card details to vendor via email.
     */
    public function sendCardToVendor(string $cardId, string $email): bool;
}
