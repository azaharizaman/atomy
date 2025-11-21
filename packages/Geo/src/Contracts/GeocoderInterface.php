<?php

declare(strict_types=1);

namespace Nexus\Geo\Contracts;

use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\GeocodeResult;
use Nexus\Geo\Exceptions\GeocodingFailedException;

/**
 * Framework-agnostic geocoding service interface
 * 
 * Implementations must support provider failover and caching
 */
interface GeocoderInterface
{
    /**
     * Geocode an address to coordinates
     * 
     * @throws GeocodingFailedException
     */
    public function geocode(string $address): GeocodeResult;

    /**
     * Reverse geocode coordinates to address
     * 
     * @throws GeocodingFailedException
     */
    public function reverseGeocode(Coordinates $coordinates): GeocodeResult;

    /**
     * Geocode with specific provider (bypasses failover chain)
     * 
     * @throws GeocodingFailedException
     */
    public function geocodeWithProvider(string $address, string $provider): GeocodeResult;

    /**
     * Get list of available providers
     * 
     * @return array<string>
     */
    public function getAvailableProviders(): array;

    /**
     * Check if provider is currently available (not circuit broken)
     */
    public function isProviderAvailable(string $provider): bool;
}
