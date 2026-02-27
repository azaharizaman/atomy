<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\FeatureFlagCollectionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * FeatureFlag API Resource.
 *
 * Exposes feature flags through the API.
 * Multi-tenant aware - returns flags for the current tenant with global fallback.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/feature-flags',
            normalizationContext: ['groups' => ['feature_flag:read']],
            provider: FeatureFlagCollectionProvider::class
        ),
    ],
    normalizationContext: ['groups' => ['feature_flag:read']],
    shortName: 'FeatureFlag',
)]
final class FeatureFlag
{
    #[Groups(['feature_flag:read'])]
    public ?string $name = null;

    #[Groups(['feature_flag:read'])]
    public ?bool $enabled = null;

    #[Groups(['feature_flag:read'])]
    public ?string $strategy = null;

    #[Groups(['feature_flag:read'])]
    public mixed $value = null;

    #[Groups(['feature_flag:read'])]
    public ?string $override = null;

    #[Groups(['feature_flag:read'])]
    public ?array $metadata = null;

    #[Groups(['feature_flag:read'])]
    public ?string $scope = null;
}
