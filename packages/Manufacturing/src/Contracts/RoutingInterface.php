<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for Routing entity.
 *
 * Represents a sequence of operations required to manufacture a product.
 * Routings are versioned and can be shared across products.
 */
interface RoutingInterface
{
    /**
     * Get the unique identifier for this routing.
     */
    public function getId(): string;

    /**
     * Get the tenant identifier.
     */
    public function getTenantId(): string;

    /**
     * Get the routing code/number.
     */
    public function getCode(): string;

    /**
     * Get the routing name/description.
     */
    public function getName(): string;

    /**
     * Get the routing version number.
     */
    public function getVersion(): int;

    /**
     * Get all operations in this routing.
     *
     * @return array<OperationInterface>
     */
    public function getOperations(): array;

    /**
     * Get total setup time in minutes across all operations.
     */
    public function getTotalSetupTime(): float;

    /**
     * Get total run time per unit in minutes across all operations.
     */
    public function getTotalRunTimePerUnit(): float;

    /**
     * Get the date this routing version becomes effective.
     */
    public function getEffectiveFrom(): ?\DateTimeImmutable;

    /**
     * Get the date this routing version expires.
     */
    public function getEffectiveTo(): ?\DateTimeImmutable;

    /**
     * Check if this routing is currently effective.
     */
    public function isEffective(\DateTimeImmutable $asOf): bool;

    /**
     * Check if this is the latest version.
     */
    public function isLatestVersion(): bool;

    /**
     * Get the product ID this routing is for.
     */
    public function getProductId(): string;

    /**
     * Get the current status (draft, released, obsolete).
     */
    public function getStatus(): string;

    /**
     * Get creation timestamp.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeImmutable;
}
