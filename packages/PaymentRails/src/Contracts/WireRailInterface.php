<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\PaymentRails\DTOs\WireTransferRequest;
use Nexus\PaymentRails\DTOs\WireTransferResult;
use Nexus\PaymentRails\Enums\WireType;
use Nexus\PaymentRails\ValueObjects\WireInstruction;

/**
 * Contract for Wire Transfer rail operations.
 *
 * Extends the base payment rail with wire transfer specific operations
 * including domestic and international wires, SWIFT messaging, and Fedwire.
 */
interface WireRailInterface extends PaymentRailInterface
{
    /**
     * Initiate a wire transfer.
     */
    public function initiateTransfer(WireTransferRequest $request): WireTransferResult;

    /**
     * Create wire instructions from a transfer request.
     */
    public function createWireInstruction(WireTransferRequest $request): WireInstruction;

    /**
     * Get the status of a wire transfer.
     */
    public function getWireStatus(string $transferId): WireTransferResult;

    /**
     * Cancel a pending wire transfer.
     */
    public function cancelWire(string $transferId, string $reason): bool;

    /**
     * Get the wire cutoff time for same-day processing.
     */
    public function getWireCutoffTime(WireType $wireType): \DateTimeImmutable;

    /**
     * Check if a wire can be processed same-day.
     */
    public function canProcessSameDay(WireType $wireType): bool;

    /**
     * Validate a SWIFT/BIC code.
     */
    public function validateSwiftCode(string $swiftCode): bool;

    /**
     * Validate an IBAN.
     */
    public function validateIban(string $iban): bool;

    /**
     * Get estimated wire fee.
     */
    public function getEstimatedFee(WireTransferRequest $request): \Nexus\Common\ValueObjects\Money;

    /**
     * Get supported currencies for international wires.
     *
     * @return array<string> ISO currency codes
     */
    public function getSupportedCurrencies(): array;
}
