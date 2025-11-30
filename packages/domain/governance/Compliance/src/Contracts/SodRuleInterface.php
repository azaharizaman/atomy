<?php

declare(strict_types=1);

namespace Nexus\Compliance\Contracts;

use Nexus\Compliance\ValueObjects\SeverityLevel;

/**
 * Interface representing a SOD rule entity.
 */
interface SodRuleInterface
{
    /**
     * Get the rule identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get the tenant identifier.
     *
     * @return string
     */
    public function getTenantId(): string;

    /**
     * Get the rule name.
     *
     * @return string
     */
    public function getRuleName(): string;

    /**
     * Get the transaction type this rule applies to.
     *
     * @return string
     */
    public function getTransactionType(): string;

    /**
     * Get the severity level.
     *
     * @return SeverityLevel
     */
    public function getSeverityLevel(): SeverityLevel;

    /**
     * Get the creator role.
     *
     * @return string
     */
    public function getCreatorRole(): string;

    /**
     * Get the approver role.
     *
     * @return string
     */
    public function getApproverRole(): string;

    /**
     * Check if the rule is active.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Get the creation timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get the last update timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getUpdatedAt(): \DateTimeImmutable;
}
