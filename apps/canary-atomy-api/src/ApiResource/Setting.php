<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\SettingCollectionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Setting API Resource.
 *
 * Exposes application settings through the API.
 * Multi-tenant aware - returns settings for the current tenant.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/settings',
            normalizationContext: ['groups' => ['setting:read']],
            provider: SettingCollectionProvider::class
        ),
    ],
    normalizationContext: ['groups' => ['setting:read']],
    shortName: 'Setting',
)]
final class Setting
{
    #[Groups(['setting:read'])]
    public ?string $key = null;

    #[Groups(['setting:read'])]
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
