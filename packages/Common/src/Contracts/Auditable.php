<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that contain audit information.
 */
interface Auditable
{
    /**
     * Get who created this.
     */
    public function getCreatedBy(): string;

    /**
     * Get when this was created.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get who last updated this.
     */
    public function getUpdatedBy(): ?string;

    /**
     * Get when this was last updated.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable;
}
