<?php

declare(strict_types=1);

namespace App\Services;

use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use GuzzleHttp\Client;
use Nexus\Connector\Exceptions\CircuitBreakerOpenException;
use Nexus\Connector\Services\ConnectorManager;
use Nexus\Geo\Contracts\GeocoderInterface;
use Nexus\Geo\Exceptions\GeocodingFailedException;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\GeocodeResult;
use Psr\Log\LoggerInterface;

final readonly class LaravelGeocoder implements GeocoderInterface
{
    private const GOOGLE_MAPS_CONNECTOR = 'google-maps-geocoding';
    private const NOMINATIM_CONNECTOR = 'nominatim-geocoding';

    public function __construct(
        private ConnectorManager $connectorManager,
        private LoggerInterface $logger,
        private string $googleMapsApiKey,
        private string $nominatimUserAgent
    ) {}

    public function geocode(string $address): GeocodeResult
    {
        // Try Google Maps first (primary provider)
        try {
            return $this->geocodeWithProvider($address, 'google-maps');
        } catch (GeocodingFailedException $e) {
            $this->logger->warning('Google Maps geocoding failed, falling back to Nominatim', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            // Fallback to Nominatim (OpenStreetMap)
            try {
                return $this->geocodeWithProvider($address, 'nominatim');
            } catch (GeocodingFailedException $nominatimError) {
                $this->logger->error('All geocoding providers failed', [
                    'address' => $address,
                    'google_error' => $e->getMessage(),
                    'nominatim_error' => $nominatimError->getMessage(),
                ]);

                throw new GeocodingFailedException(
                    "Geocoding failed for address '{$address}'. All providers exhausted."
                );
            }
        }
    }

    public function reverseGeocode(Coordinates $coordinates): string
    {
        // Try Google Maps first
        try {
            return $this->reverseGeocodeWithProvider($coordinates, 'google-maps');
        } catch (GeocodingFailedException $e) {
            $this->logger->warning('Google Maps reverse geocoding failed, falling back to Nominatim', [
                'coordinates' => $coordinates->toString(),
                'error' => $e->getMessage(),
            ]);

            // Fallback to Nominatim
            return $this->reverseGeocodeWithProvider($coordinates, 'nominatim');
        }
    }

    private function geocodeWithProvider(string $address, string $provider): GeocodeResult
    {
        $connectorId = $provider === 'google-maps' ? self::GOOGLE_MAPS_CONNECTOR : self::NOMINATIM_CONNECTOR;

        try {
            $geocoder = $this->createGeocoder($provider);
            
            $result = $this->connectorManager->executeWithCircuitBreaker(
                $connectorId,
                fn() => $geocoder->geocodeQuery(GeocodeQuery::create($address))
            );

            if ($result->isEmpty()) {
                throw new GeocodingFailedException("No results found for address: {$address}");
            }

            $location = $result->first();
            $coords = $location->getCoordinates();

            return new GeocodeResult(
                address: $location->getFormattedAddress() ?? $address,
                coordinates: new Coordinates(
                    latitude: $coords->getLatitude(),
                    longitude: $coords->getLongitude()
                ),
                provider: $provider,
                metadata: [
                    'country_code' => $location->getCountry()?->getCode(),
                    'locality' => $location->getLocality(),
                    'postal_code' => $location->getPostalCode(),
                ]
            );
        } catch (CircuitBreakerOpenException $e) {
            throw new GeocodingFailedException(
                "Circuit breaker open for {$provider}: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            throw new GeocodingFailedException(
                "Geocoding with {$provider} failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    private function reverseGeocodeWithProvider(Coordinates $coordinates, string $provider): string
    {
        $connectorId = $provider === 'google-maps' ? self::GOOGLE_MAPS_CONNECTOR : self::NOMINATIM_CONNECTOR;

        try {
            $geocoder = $this->createGeocoder($provider);
            
            $result = $this->connectorManager->executeWithCircuitBreaker(
                $connectorId,
                fn() => $geocoder->reverseQuery(
                    \Geocoder\Query\ReverseQuery::fromCoordinates(
                        $coordinates->latitude,
                        $coordinates->longitude
                    )
                )
            );

            if ($result->isEmpty()) {
                throw new GeocodingFailedException(
                    "No address found for coordinates: {$coordinates->toString()}"
                );
            }

            return $result->first()->getFormattedAddress() ?? $coordinates->toString();
        } catch (CircuitBreakerOpenException $e) {
            throw new GeocodingFailedException(
                "Circuit breaker open for {$provider}: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            throw new GeocodingFailedException(
                "Reverse geocoding with {$provider} failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    private function createGeocoder(string $provider): StatefulGeocoder
    {
        $httpClient = new Client(['timeout' => 5]);

        if ($provider === 'google-maps') {
            $providerInstance = new GoogleMaps($httpClient, null, $this->googleMapsApiKey);
        } else {
            $providerInstance = Nominatim::withOpenStreetMapServer($httpClient, $this->nominatimUserAgent);
        }

        return new StatefulGeocoder($providerInstance, 'en');
    }
}
