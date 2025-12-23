<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\PaymentRails\DTOs\AchBatchRequest;
use Nexus\PaymentRails\DTOs\AchBatchResult;
use Nexus\PaymentRails\DTOs\AchPrenoteRequest;
use Nexus\PaymentRails\ValueObjects\AchFile;
use Nexus\PaymentRails\ValueObjects\AchNotificationOfChange;
use Nexus\PaymentRails\ValueObjects\AchReturn;

/**
 * Contract for ACH (Automated Clearing House) rail operations.
 *
 * Extends the base payment rail with ACH-specific operations
 * including batch processing, NACHA file generation, and return handling.
 */
interface AchRailInterface extends PaymentRailInterface
{
    /**
     * Create an ACH batch from a batch request.
     */
    public function createBatch(AchBatchRequest $request): AchBatchResult;

    /**
     * Submit an ACH file for processing.
     */
    public function submitFile(AchFile $file): AchBatchResult;

    /**
     * Generate a NACHA-formatted file from a batch request.
     */
    public function generateNachaFile(AchBatchRequest $request): string;

    /**
     * Parse a NACHA file content.
     */
    public function parseNachaFile(string $content): AchFile;

    /**
     * Send a prenote for account validation.
     */
    public function sendPrenote(AchPrenoteRequest $request): AchBatchResult;

    /**
     * Process an ACH return notification.
     */
    public function processReturn(AchReturn $return): bool;

    /**
     * Process a Notification of Change (NOC).
     */
    public function processNoc(AchNotificationOfChange $noc): bool;

    /**
     * Check if same-day ACH is available.
     */
    public function isSameDayAvailable(): bool;

    /**
     * Get the ACH cutoff time for today.
     */
    public function getCutoffTime(): \DateTimeImmutable;

    /**
     * Get the next available effective entry date.
     *
     * @param bool $isSameDay Whether to use same-day ACH
     */
    public function getNextEffectiveDate(bool $isSameDay = false): \DateTimeImmutable;

    /**
     * Validate routing number for ACH transactions.
     */
    public function validateRoutingNumber(string $routingNumber): bool;
}
