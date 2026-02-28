<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\State\FeatureFlagCollectionProvider;
use App\State\FeatureFlagItemProvider;
use App\State\FeatureFlagProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * FeatureFlag API Resource.
 *
 * Exposes feature flags through the SettingsManagement orchestrator.
 * Multi-tenant aware - returns flags for the current tenant with global fallback.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/feature-flags',
            normalizationContext: ['groups' => ['feature_flag:read']],
            provider: FeatureFlagCollectionProvider::class
        ),
        new Get(
            uriTemplate: '/feature-flags/{name}',
            normalizationContext: ['groups' => ['feature_flag:read']],
            provider: FeatureFlagItemProvider::class
        ),
        new Patch(
            uriTemplate: '/feature-flags/{name}',
            denormalizationContext: ['groups' => ['feature_flag:write']],
            normalizationContext: ['groups' => ['feature_flag:read']],
            processor: FeatureFlagProcessor::class
        ),
    ],
    normalizationContext: ['groups' => ['feature_flag:read']],
    shortName: 'FeatureFlag',
)]
final class FeatureFlag
{
    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    public ?string $name = null;

    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    public ?bool $enabled = null;

    #[Groups(['feature_flag:read'])]
    public ?string $strategy = null;

    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    public mixed $value = null;

    #[Groups(['feature_flag:read'])]
    public ?string $override = null;

    #[Groups(['feature_flag:read'])]
    public ?array $metadata = null;

    #[Groups(['feature_flag:read'])]
    public ?string $scope = null;
}
