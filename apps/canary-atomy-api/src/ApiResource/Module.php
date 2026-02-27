<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\State\ModuleCollectionProvider;
use App\State\ModuleItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Module API Resource.
 *
 * Represents a module that can be available or installed.
 * This is a read-only resource that shows all available modules
 * and their installation status.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/modules',
            normalizationContext: ['groups' => ['module:read']],
            provider: ModuleCollectionProvider::class
        ),
        new Get(
            uriTemplate: '/modules/{moduleId}',
            normalizationContext: ['groups' => ['module:read']],
            provider: ModuleItemProvider::class
        ),
    ],
    normalizationContext: ['groups' => ['module:read']],
    shortName: 'Module',
)]
final class Module
{
    #[ApiProperty(identifier: true)]
    #[Groups(['module:read'])]
    public ?string $id = null;

    #[Groups(['module:read'])]
    public ?string $moduleId = null;

    #[Groups(['module:read'])]
    public ?string $name = null;

    #[Groups(['module:read'])]
    public ?string $description = null;

    #[Groups(['module:read'])]
    public ?string $version = null;

    #[Groups(['module:read'])]
    public ?bool $isInstalled = null;

    #[Groups(['module:read'])]
    public ?string $installedAt = null;

    #[Groups(['module:read'])]
    public ?string $installedBy = null;
}
