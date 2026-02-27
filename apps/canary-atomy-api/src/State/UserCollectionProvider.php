<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\User as UserResource;

/**
 * Collection provider for User resource.
 *
 * This returns sample data for testing.
 * In production, this would query the User entity filtered by tenant.
 */
final class UserCollectionProvider implements ProviderInterface
{
    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<UserResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        // This is sample data for testing
        // In production, this would fetch from the User entity filtered by tenant
        $sampleUsers = [
            [
                'id' => 'user-001',
                'email' => 'admin@example.com',
                'name' => 'Admin User',
                'status' => 'active',
                'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
                'createdAt' => '2024-01-01T00:00:00+00:00',
            ],
            [
                'id' => 'user-002',
                'email' => 'john.doe@example.com',
                'name' => 'John Doe',
                'status' => 'active',
                'roles' => ['ROLE_USER'],
                'createdAt' => '2024-01-15T00:00:00+00:00',
            ],
            [
                'id' => 'user-003',
                'email' => 'jane.smith@example.com',
                'name' => 'Jane Smith',
                'status' => 'active',
                'roles' => ['ROLE_USER'],
                'createdAt' => '2024-02-01T00:00:00+00:00',
            ],
        ];

        foreach ($sampleUsers as $userData) {
            $user = new UserResource();
            $user->id = $userData['id'];
            $user->email = $userData['email'];
            $user->name = $userData['name'];
            $user->status = $userData['status'];
            $user->roles = $userData['roles'];
            $user->createdAt = $userData['createdAt'];

            yield $user;
        }
    }
}
