<?php

declare(strict_types=1);

namespace Nexus\Geo\Services;

use Nexus\Geo\Contracts\GeocoderInterface;
use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\GeocodeResult;
use Nexus\Geo\Exceptions\GeocodingFailedException;
use Psr\Log\LoggerInterface;

/**
 * Stateless geocoding manager with cache and provider failover
 * 
 * This is the public API for geocoding operations
 */
final readonly class GeocodingManager
{
    public function __construct(
        private GeocoderInterface $geocoder,
        private GeoRepositoryInterface $repository,
        private LoggerInterface $logger,
        private string $tenantId
    ) {
    }

    /**
     * Geocode address with cache-first strategy
     */
    public function geocode(string $address): GeocodeResult
    {
        // Try cache first
        $cached = $this->repository->getCachedGeocode($address, $this->tenantId);
        if ($cached !== null) {
            $this->logger->info('Geocode cache hit', ['address' => $address]);
            return $cached;
        }

        // Cache miss - geocode and store
        $this->logger->info('Geocode cache miss', ['address' => $address]);
        
        try {
            $result = $this->geocoder->geocode($address);
            $this->repository->cacheGeocode($address, $result, $this->tenantId);
            
            return $result;
        } catch (GeocodingFailedException $e) {
            $this->logger->error('Geocoding failed', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reverse geocode coordinates to address
     */
    public function reverseGeocode(Coordinates $coordinates): GeocodeResult
    {
        try {
            return $this->geocoder->reverseGeocode($coordinates);
        } catch (GeocodingFailedException $e) {
            $this->logger->error('Reverse geocoding failed', [
                'coordinates' => $coordinates->toString(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Geocode with specific provider (bypasses cache)
     */
    public function geocodeWithProvider(string $address, string $provider): GeocodeResult
    {
        return $this->geocoder->geocodeWithProvider($address, $provider);
    }

    /**
     * Get available geocoding providers
     * 
     * @return array<string>
     */
    public function getAvailableProviders(): array
    {
        return $this->geocoder->getAvailableProviders();
    }

    /**
     * Check provider availability (circuit breaker status)
     */
    public function isProviderAvailable(string $provider): bool
    {
        return $this->geocoder->isProviderAvailable($provider);
    }
}
