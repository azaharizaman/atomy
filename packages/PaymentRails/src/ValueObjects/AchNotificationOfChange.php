<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\PaymentRails\Enums\NocCode;

/**
 * Represents a Notification of Change (NOC) from an ACH transaction.
 *
 * NOCs are sent by the RDFI when they need to correct account information
 * for future transactions. They indicate what data needs to be updated.
 */
final class AchNotificationOfChange
{
    /**
     * @param string $originalTraceNumber Trace number of the original entry
     * @param NocCode $nocCode The notification of change code
     * @param string $correctedData The corrected data from the RDFI
     * @param \DateTimeImmutable $nocDate Date the NOC was received
     * @param string|null $originalEntryId Original entry identifier
     */
    public function __construct(
        public readonly string $originalTraceNumber,
        public readonly NocCode $nocCode,
        public readonly string $correctedData,
        public readonly \DateTimeImmutable $nocDate,
        public readonly ?string $originalEntryId = null,
    ) {}

    /**
     * Create a NOC from received data.
     */
    public static function create(
        string $originalTraceNumber,
        NocCode $nocCode,
        string $correctedData,
        ?\DateTimeImmutable $nocDate = null,
    ): self {
        return new self(
            originalTraceNumber: $originalTraceNumber,
            nocCode: $nocCode,
            correctedData: $correctedData,
            nocDate: $nocDate ?? new \DateTimeImmutable(),
        );
    }

    /**
     * Get the fields that need to be updated.
     *
     * @return array<string>
     */
    public function getFieldsToUpdate(): array
    {
        return $this->nocCode->fieldsToUpdate();
    }

    /**
     * Check if this NOC affects account information.
     */
    public function affectsAccountInfo(): bool
    {
        return $this->nocCode->affectsAccountInfo();
    }

    /**
     * Check if this NOC affects company information.
     */
    public function affectsCompanyInfo(): bool
    {
        return $this->nocCode->affectsCompanyInfo();
    }

    /**
     * Parse the corrected data to extract individual fields.
     *
     * @return array<string, string>
     */
    public function parseCorrectionsData(): array
    {
        $fields = $this->getFieldsToUpdate();
        $corrections = [];

        // NOC corrected data is position-based in the NACHA format
        // This is a simplified parser - actual parsing depends on specific NOC code
        $data = $this->correctedData;

        if (in_array('routing_number', $fields, true) && mb_strlen($data) >= 9) {
            $corrections['routing_number'] = mb_substr($data, 0, 9);
            $data = mb_substr($data, 9);
        }

        if (in_array('account_number', $fields, true) && mb_strlen($data) >= 17) {
            $corrections['account_number'] = trim(mb_substr($data, 0, 17));
            $data = mb_substr($data, 17);
        }

        if (in_array('transaction_code', $fields, true) && mb_strlen($data) >= 2) {
            $corrections['transaction_code'] = mb_substr($data, 0, 2);
            $data = mb_substr($data, 2);
        }

        if (in_array('individual_id', $fields, true) && mb_strlen($data) >= 22) {
            $corrections['individual_id'] = trim(mb_substr($data, 0, 22));
            $data = mb_substr($data, 22);
        }

        if (in_array('individual_name', $fields, true) && mb_strlen($data) >= 22) {
            $corrections['individual_name'] = trim(mb_substr($data, 0, 22));
        }

        return $corrections;
    }

    /**
     * Get the corrected routing number if applicable.
     */
    public function getCorrectedRoutingNumber(): ?RoutingNumber
    {
        $corrections = $this->parseCorrectionsData();

        if (!isset($corrections['routing_number'])) {
            return null;
        }

        return RoutingNumber::tryFromString($corrections['routing_number']);
    }

    /**
     * Get the corrected account number if applicable.
     */
    public function getCorrectedAccountNumber(): ?string
    {
        $corrections = $this->parseCorrectionsData();

        return $corrections['account_number'] ?? null;
    }

    /**
     * Get the description of what changed.
     */
    public function getDescription(): string
    {
        return $this->nocCode->description();
    }

    /**
     * Check if this is a critical NOC requiring immediate attention.
     */
    public function isCritical(): bool
    {
        // NOCs affecting routing or account info are critical
        return $this->affectsAccountInfo();
    }

    /**
     * Get the days since the NOC was received.
     */
    public function getDaysSinceNoc(): int
    {
        $now = new \DateTimeImmutable();

        return $this->nocDate->diff($now)->days;
    }

    /**
     * Check if the NOC is within the update window.
     *
     * NACHA rules require updating records within 6 banking days.
     */
    public function isWithinUpdateWindow(): bool
    {
        return $this->getDaysSinceNoc() <= 6;
    }
}
