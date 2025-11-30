<?php

declare(strict_types=1);

namespace Nexus\Compliance\Contracts;

/**
 * Interface representing a compliance scheme entity.
 */
interface ComplianceSchemeInterface
{
    /**
     * Get the scheme identifier.
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
     * Get the scheme name (e.g., 'ISO14001', 'SOX').
     *
     * @return string
     */
    public function getSchemeName(): string;

    /**
     * Check if the scheme is currently active.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Get the activation timestamp.
     *
     * @return \DateTimeImmutable|null
     */
    public function getActivatedAt(): ?\DateTimeImmutable;

    /**
     * Get the scheme configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;

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
