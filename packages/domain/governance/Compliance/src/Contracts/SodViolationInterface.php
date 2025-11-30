<?php

declare(strict_types=1);

namespace Nexus\Compliance\Contracts;

/**
 * Interface representing a SOD violation entity.
 */
interface SodViolationInterface
{
    /**
     * Get the violation identifier.
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
     * Get the rule ID that was violated.
     *
     * @return string
     */
    public function getRuleId(): string;

    /**
     * Get the transaction ID.
     *
     * @return string
     */
    public function getTransactionId(): string;

    /**
     * Get the transaction type.
     *
     * @return string
     */
    public function getTransactionType(): string;

    /**
     * Get the creator user ID.
     *
     * @return string
     */
    public function getCreatorId(): string;

    /**
     * Get the approver user ID.
     *
     * @return string
     */
    public function getApproverId(): string;

    /**
     * Get the violation timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getViolatedAt(): \DateTimeImmutable;

    /**
     * Get the creation timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable;
}
