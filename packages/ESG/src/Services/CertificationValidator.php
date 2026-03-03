<?php

declare(strict_types=1);

namespace Nexus\ESG\Services;

use Nexus\ESG\ValueObjects\CertificationMetadata;
use Nexus\Common\Contracts\ClockInterface;

/**
 * Service for validating sustainability certifications.
 */
final readonly class CertificationValidator
{
    public function __construct(
        private ClockInterface $clock
    ) {
    }

    /**
     * Check if a certification is valid and not expired.
     */
    public function isValid(CertificationMetadata $certification): bool
    {
        return $certification->isActiveAt($this->clock->now());
    }

    /**
     * Get the number of days until a certificate expires.
     */
    public function getDaysUntilExpiry(CertificationMetadata $certification): ?int
    {
        if ($certification->expiresAt === null) {
            return null;
        }

        $now = $this->clock->now();
        if ($now > $certification->expiresAt) {
            return 0;
        }

        $diff = $now->diff($certification->expiresAt);
        return (int)$diff->format('%a');
    }
}
