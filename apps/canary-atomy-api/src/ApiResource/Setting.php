<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\SettingCollectionProvider;
use App\State\SettingItemProvider;
use App\State\SettingProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Setting API Resource.
 *
 * Exposes application settings through the SettingsManagement orchestrator.
 * Multi-tenant aware - returns settings for the current tenant.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/settings',
            normalizationContext: ['groups' => ['setting:read']],
            provider: SettingCollectionProvider::class
        ),
        new Get(
            uriTemplate: '/settings/{key}',
            requirements: ['key' => '.+'],
            normalizationContext: ['groups' => ['setting:read']],
            provider: SettingItemProvider::class
        ),
        new Patch(
            uriTemplate: '/settings/{key}',
            requirements: ['key' => '.+'],
            denormalizationContext: ['groups' => ['setting:write']],
            normalizationContext: ['groups' => ['setting:read']],
            processor: SettingProcessor::class
        ),
        new Post(
            uriTemplate: '/settings/bulk-update',
            denormalizationContext: ['groups' => ['setting:write']],
            normalizationContext: ['groups' => ['setting:read']],
            processor: SettingProcessor::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'Bulk update settings')
        ),
    ],
    normalizationContext: ['groups' => ['setting:read']],
    shortName: 'Setting',
)]
final class Setting
{
    #[Groups(['setting:read', 'setting:write'])]
    public ?string $key = null;

    #[Groups(['setting:read', 'setting:write'])]
    public mixed $value = null;

    #[Groups(['setting:read'])]
    public ?string $type = null;

    #[Groups(['setting:read'])]
    public ?bool $isEncrypted = null;

    #[Groups(['setting:read'])]
    public ?string $scope = null;

    #[Groups(['setting:read'])]
    public ?bool $isReadOnly = null;
}
