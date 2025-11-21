<?php

declare(strict_types=1);

namespace Nexus\Geo\Exceptions;

/**
 * Exception thrown when geocoding operation fails
 */
class GeocodingFailedException extends GeoException
{
    public static function providerError(string $provider, string $message): self
    {
        return new self("Geocoding failed via provider '{$provider}': {$message}");
    }

    public static function addressNotFound(string $address): self
    {
        return new self("Could not geocode address: {$address}");
    }

    public static function allProvidersFailed(): self
    {
        return new self('All geocoding providers failed to geocode the address');
    }

    public static function rateLimitExceeded(string $provider): self
    {
        return new self("Rate limit exceeded for provider '{$provider}'");
    }
}
