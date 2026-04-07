<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for the alpha company onboarding flow.
 */
final readonly class TenantCompanyOnboardingRequest
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $tenantCode,
        public string $companyName,
        public string $ownerName,
        public string $ownerEmail,
        public string $ownerPassword,
        public ?string $timezone = null,
        public ?string $locale = null,
        public ?string $currency = null,
        public ?array $metadata = null,
    ) {
    }

    public function getOwnerFirstName(): string
    {
        $parts = preg_split('/\s+/', trim($this->ownerName)) ?: [];

        return $parts[0] ?? '';
    }

    public function getOwnerLastName(): string
    {
        $parts = preg_split('/\s+/', trim($this->ownerName)) ?: [];

        if (count($parts) <= 1) {
            return '';
        }

        return trim(implode(' ', array_slice($parts, 1)));
    }
}
