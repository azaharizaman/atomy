<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\UserCollectionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * User API Resource.
 *
 * Exposes user data through the API.
 * Multi-tenant aware - filters users by current tenant.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/users',
            normalizationContext: ['groups' => ['user:read']],
            provider: UserCollectionProvider::class
        ),
    ],
    normalizationContext: ['groups' => ['user:read']],
    shortName: 'User',
)]
final class User
{
    #[Groups(['user:read'])]
    public ?string $id = null;

    #[Groups(['user:read'])]
    public ?string $email = null;

    #[Groups(['user:read'])]
    public ?string $name = null;

    #[Groups(['user:read'])]
    public ?string $status = null;

    #[Groups(['user:read'])]
    public ?array $roles = [];

    #[Groups(['user:read'])]
    public ?string $createdAt = null;
}
