<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\User as UserResource;
use App\Repository\UserRepository;
use App\Service\TenantContext;

/**
 * Collection provider for User resource.
 *
 * Fetches users from the database, filtered by tenant context.
 */
final class UserCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<UserResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        $criteria = [];
        if ($tenantId !== null) {
            $criteria['tenantId'] = $tenantId;
        }

        $users = $this->userRepository->findBy($criteria);

        foreach ($users as $userData) {
            $user = new UserResource();
            $user->id = $userData->getId();
            $user->email = $userData->getEmail();
            $user->name = $userData->getName();
            $user->status = $userData->getStatus();
            $user->roles = $userData->getRoles();
            $user->createdAt = $userData->getCreatedAt()->format(\DateTimeInterface::ISO8601);

            yield $user;
        }
    }
}
