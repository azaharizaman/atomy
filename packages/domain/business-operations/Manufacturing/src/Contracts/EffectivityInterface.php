<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for entities that support effectivity dates.
 *
 * Used for BOMs, Routings, and BOM lines to support engineering change orders.
 */
interface EffectivityInterface
{
    /**
     * Get the date this entity becomes effective.
     * Null means effective immediately (no start constraint).
     */
    public function getEffectiveFrom(): ?\DateTimeImmutable;

    /**
     * Get the date this entity expires.
     * Null means never expires (no end constraint).
     */
    public function getEffectiveTo(): ?\DateTimeImmutable;

    /**
     * Check if this entity is effective at the given date.
     *
     * @param \DateTimeImmutable $asOf The date to check effectivity against
     */
    public function isEffective(\DateTimeImmutable $asOf): bool;
}
