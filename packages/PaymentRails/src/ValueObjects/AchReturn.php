<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\PaymentRails\Enums\AchReturnCode;
use Nexus\PaymentRails\Enums\EntryStatus;

/**
 * Represents an ACH return notification.
 *
 * When an ACH entry is returned by the receiving bank, a return
 * is generated with a return code explaining the reason.
 */
final class AchReturn
{
    /**
     * @param string $originalTraceNumber Trace number of the original entry
     * @param AchReturnCode $returnCode The reason for the return
     * @param \DateTimeImmutable $returnDate Date the return was received
     * @param string|null $originalEntryId Original entry identifier
     * @param string|null $addendaInformation Additional return information
     * @param Money|null $originalAmount Original transaction amount
     * @param bool $dishonored Whether this is a dishonored return
     * @param bool $contested Whether this is a contested return
     */
    public function __construct(
        public readonly string $originalTraceNumber,
        public readonly AchReturnCode $returnCode,
        public readonly \DateTimeImmutable $returnDate,
        public readonly ?string $originalEntryId = null,
        public readonly ?string $addendaInformation = null,
        public readonly ?\Nexus\Common\ValueObjects\Money $originalAmount = null,
        public readonly bool $dishonored = false,
        public readonly bool $contested = false,
    ) {}

    /**
     * Create a return from a return record.
     */
    public static function create(
        string $originalTraceNumber,
        AchReturnCode $returnCode,
        ?\DateTimeImmutable $returnDate = null,
    ): self {
        return new self(
            originalTraceNumber: $originalTraceNumber,
            returnCode: $returnCode,
            returnDate: $returnDate ?? new \DateTimeImmutable(),
        );
    }

    /**
     * Check if this return is retriable.
     */
    public function isRetriable(): bool
    {
        return $this->returnCode->isRetriable() && !$this->dishonored;
    }

    /**
     * Check if this return is administrative.
     */
    public function isAdministrative(): bool
    {
        return $this->returnCode->isAdministrative();
    }

    /**
     * Get the suggested action for this return.
     */
    public function getSuggestedAction(): string
    {
        return $this->returnCode->suggestedAction();
    }

    /**
     * Check if this return indicates a closed account.
     */
    public function isAccountClosed(): bool
    {
        return in_array($this->returnCode, [
            AchReturnCode::R02,
            AchReturnCode::R16,
        ], true);
    }

    /**
     * Check if this return indicates authorization issues.
     */
    public function isAuthorizationIssue(): bool
    {
        return in_array($this->returnCode, [
            AchReturnCode::R07,
            AchReturnCode::R10,
            AchReturnCode::R29,
        ], true);
    }

    /**
     * Check if this return indicates invalid account info.
     */
    public function isInvalidAccountInfo(): bool
    {
        return in_array($this->returnCode, [
            AchReturnCode::R03,
            AchReturnCode::R04,
            AchReturnCode::R13,
        ], true);
    }

    /**
     * Get the return code description.
     */
    public function getDescription(): string
    {
        return $this->returnCode->description();
    }

    /**
     * Create a dishonored return.
     */
    public function asDishonored(): self
    {
        return new self(
            originalTraceNumber: $this->originalTraceNumber,
            returnCode: $this->returnCode,
            returnDate: $this->returnDate,
            originalEntryId: $this->originalEntryId,
            addendaInformation: $this->addendaInformation,
            originalAmount: $this->originalAmount,
            dishonored: true,
            contested: $this->contested,
        );
    }

    /**
     * Create a contested return.
     */
    public function asContested(): self
    {
        return new self(
            originalTraceNumber: $this->originalTraceNumber,
            returnCode: $this->returnCode,
            returnDate: $this->returnDate,
            originalEntryId: $this->originalEntryId,
            addendaInformation: $this->addendaInformation,
            originalAmount: $this->originalAmount,
            dishonored: $this->dishonored,
            contested: true,
        );
    }

    /**
     * Calculate days since the return was received.
     */
    public function getDaysSinceReturn(): int
    {
        $now = new \DateTimeImmutable();

        return $this->returnDate->diff($now)->days;
    }

    /**
     * Check if the return is within the contestation window.
     *
     * Most returns can be contested within 5 banking days.
     */
    public function isWithinContestationWindow(): bool
    {
        return $this->getDaysSinceReturn() <= 5 && !$this->contested;
    }
}
