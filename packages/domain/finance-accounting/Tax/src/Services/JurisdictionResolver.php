<?php

declare(strict_types=1);

namespace Nexus\Tax\Services;

use Nexus\Tax\Contracts\TaxJurisdictionResolverInterface;
use Nexus\Tax\Enums\TaxLevel;
use Nexus\Tax\Exceptions\JurisdictionNotResolvedException;
use Nexus\Tax\ValueObjects\TaxContext;
use Nexus\Tax\ValueObjects\TaxJurisdiction;
use Psr\Log\LoggerInterface;

/**
 * Jurisdiction Resolver Service
 * 
 * Resolves tax jurisdiction from address using:
 * - Direct database lookup (jurisdiction table)
 * - Geocoding fallback (Nexus\Geo integration)
 * - Place-of-supply rules for cross-border
 * 
 * Application layer must provide concrete implementation.
 * This is a reference implementation showing the logic.
 */
final readonly class JurisdictionResolver implements TaxJurisdictionResolverInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function resolve(TaxContext $context): TaxJurisdiction
    {
        $this->logger?->info('Resolving jurisdiction', [
            'transaction_id' => $context->transactionId,
            'destination_country' => $context->destinationAddress['country'] ?? null,
        ]);

        try {
            // Check if cross-border transaction
            if ($context->isCrossBorder()) {
                return $this->resolveCrossBorder($context);
            }

            // Standard domestic transaction
            return $this->resolveFromAddress($context->destinationAddress);

        } catch (\Throwable $e) {
            $this->logger?->error('Jurisdiction resolution failed', [
                'transaction_id' => $context->transactionId,
                'error' => $e->getMessage(),
            ]);

            throw new JurisdictionNotResolvedException(
                $context->destinationAddress,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolveFromAddress(array $address): TaxJurisdiction
    {
        // Validate address has country
        if (!isset($address['country']) || empty($address['country'])) {
            throw new JurisdictionNotResolvedException(
                $address,
                'Address must include country code'
            );
        }

        $countryCode = $address['country'];

        // Build jurisdiction based on address granularity
        return match ($countryCode) {
            'US' => $this->resolveUSJurisdiction($address),
            'CA' => $this->resolveCanadianJurisdiction($address),
            'GB' => $this->resolveUKJurisdiction($address),
            'MY' => $this->resolveMalaysianJurisdiction($address),
            default => $this->resolveGenericJurisdiction($address),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function resolveHierarchy(array $address): array
    {
        $jurisdiction = $this->resolveFromAddress($address);
        $hierarchy = [];

        // Traverse up the hierarchy
        $current = $jurisdiction;
        while ($current !== null) {
            $hierarchy[] = $current;
            $current = $current->parent;
        }

        // Reverse to get federal → local order
        return array_reverse($hierarchy);
    }

    /**
     * {@inheritdoc}
     */
    public function isInJurisdiction(array $address, string $jurisdictionCode): bool
    {
        try {
            $resolved = $this->resolveFromAddress($address);

            // Check if resolved jurisdiction matches or is within target
            if ($resolved->code === $jurisdictionCode) {
                return true;
            }

            // Check parent hierarchy
            return $resolved->isWithin(new TaxJurisdiction(
                code: $jurisdictionCode,
                name: 'Target Jurisdiction',
                level: TaxLevel::Federal,
                countryCode: substr($jurisdictionCode, 0, 2)
            ));

        } catch (JurisdictionNotResolvedException) {
            return false;
        }
    }

    /**
     * Resolve cross-border transaction using place-of-supply rules
     */
    private function resolveCrossBorder(TaxContext $context): TaxJurisdiction
    {
        // Apply place-of-supply rules based on service classification
        if ($context->serviceClassification?->requiresPlaceOfSupplyLogic()) {
            // Digital services: customer location (B2C) or supplier location (B2B)
            // For simplicity, using destination address
            // Real implementation would check customer type (B2B vs B2C)
            return $this->resolveFromAddress($context->destinationAddress);
        }

        // Physical goods: destination
        return $this->resolveFromAddress($context->destinationAddress);
    }

    /**
     * Resolve US jurisdiction (Federal → State → County → City)
     */
    private function resolveUSJurisdiction(array $address): TaxJurisdiction
    {
        $stateCode = $address['state'] ?? null;
        $city = $address['city'] ?? null;
        $county = $address['county'] ?? null;

        if (!$stateCode) {
            throw new JurisdictionNotResolvedException(
                $address,
                'US address must include state code'
            );
        }

        // Build hierarchy: USA (Federal) → State → County → City
        $federal = new TaxJurisdiction(
            code: 'US',
            name: 'United States',
            level: TaxLevel::Federal,
            countryCode: 'US'
        );

        $state = new TaxJurisdiction(
            code: "US-{$stateCode}",
            name: $this->getUSStateName($stateCode),
            level: TaxLevel::State,
            countryCode: 'US',
            stateCode: $stateCode,
            parent: $federal
        );

        // If city provided, add local level
        if ($city) {
            $citySlug = strtoupper(substr(preg_replace('/[^a-z]/i', '', $city), 0, 3));
            return new TaxJurisdiction(
                code: "US-{$stateCode}-{$citySlug}",
                name: "{$city}, {$stateCode}",
                level: TaxLevel::Municipal,
                countryCode: 'US',
                stateCode: $stateCode,
                cityCode: $citySlug,
                parent: $state
            );
        }

        return $state;
    }

    /**
     * Resolve Canadian jurisdiction (Federal → Province)
     */
    private function resolveCanadianJurisdiction(array $address): TaxJurisdiction
    {
        $provinceCode = $address['state'] ?? null;

        if (!$provinceCode) {
            throw new JurisdictionNotResolvedException(
                $address,
                'Canadian address must include province code'
            );
        }

        $federal = new TaxJurisdiction(
            code: 'CA',
            name: 'Canada',
            level: TaxLevel::Federal,
            countryCode: 'CA'
        );

        return new TaxJurisdiction(
            code: "CA-{$provinceCode}",
            name: $this->getCanadianProvinceName($provinceCode),
            level: TaxLevel::State,
            countryCode: 'CA',
            stateCode: $provinceCode,
            parent: $federal
        );
    }

    /**
     * Resolve UK jurisdiction
     */
    private function resolveUKJurisdiction(array $address): TaxJurisdiction
    {
        return new TaxJurisdiction(
            code: 'GB',
            name: 'United Kingdom',
            level: TaxLevel::Federal,
            countryCode: 'GB'
        );
    }

    /**
     * Resolve Malaysian jurisdiction
     */
    private function resolveMalaysianJurisdiction(array $address): TaxJurisdiction
    {
        return new TaxJurisdiction(
            code: 'MY',
            name: 'Malaysia',
            level: TaxLevel::Federal,
            countryCode: 'MY'
        );
    }

    /**
     * Resolve generic jurisdiction (country-level only)
     */
    private function resolveGenericJurisdiction(array $address): TaxJurisdiction
    {
        $countryCode = $address['country'];

        return new TaxJurisdiction(
            code: $countryCode,
            name: $this->getCountryName($countryCode),
            level: TaxLevel::Federal,
            countryCode: $countryCode
        );
    }

    /**
     * Get US state name from code
     */
    private function getUSStateName(string $code): string
    {
        return match ($code) {
            'CA' => 'California',
            'NY' => 'New York',
            'TX' => 'Texas',
            'FL' => 'Florida',
            'IL' => 'Illinois',
            'PA' => 'Pennsylvania',
            default => $code,
        };
    }

    /**
     * Get Canadian province name from code
     */
    private function getCanadianProvinceName(string $code): string
    {
        return match ($code) {
            'ON' => 'Ontario',
            'QC' => 'Quebec',
            'BC' => 'British Columbia',
            'AB' => 'Alberta',
            'MB' => 'Manitoba',
            'SK' => 'Saskatchewan',
            default => $code,
        };
    }

    /**
     * Get country name from code
     */
    private function getCountryName(string $code): string
    {
        return match ($code) {
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'MY' => 'Malaysia',
            'SG' => 'Singapore',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            default => $code,
        };
    }
}
