<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\TenantCollectionProvider;
use App\State\TenantItemProvider;
use App\State\TenantOnboardingProcessor;
use App\State\TenantLifecycleProcessor;
use Nexus\Tenant\Enums\TenantStatus;
use Nexus\Tenant\Enums\TenantPlan;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Tenant API Resource.
 *
 * Exposes tenant management through TenantOperations orchestrator.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/tenants',
            normalizationContext: ['groups' => ['tenant:read']],
            provider: TenantCollectionProvider::class,
            security: 'is_granted("ROLE_USER")'
        ),
        new Get(
            uriTemplate: '/tenants/{id}',
            normalizationContext: ['groups' => ['tenant:read']],
            provider: TenantItemProvider::class,
            security: 'is_granted("ROLE_USER") and object.id == user.tenantId'
        ),
        new Post(
            uriTemplate: '/tenants',
            denormalizationContext: ['groups' => ['tenant:write']],
            normalizationContext: ['groups' => ['tenant:read']],
            processor: TenantOnboardingProcessor::class,
            security: 'is_granted("ROLE_SUPER_ADMIN")'
        ),
        new Post(
            uriTemplate: '/tenants/{id}/suspend',
            status: 200,
            controller: 'App\Controller\TenantLifecycleController::suspend',
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'Suspend a tenant'),
            security: 'is_granted("ROLE_SUPER_ADMIN") or (is_granted("ROLE_TENANT_ADMIN") and id == user.tenantId)'
        ),
        new Post(
            uriTemplate: '/tenants/{id}/activate',
            status: 200,
            controller: 'App\Controller\TenantLifecycleController::activate',
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'Activate a suspended tenant'),
            security: 'is_granted("ROLE_SUPER_ADMIN")'
        ),
        new Post(
            uriTemplate: '/tenants/{id}/archive',
            status: 200,
            controller: 'App\Controller\TenantLifecycleController::archive',
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'Archive a tenant'),
            security: 'is_granted("ROLE_SUPER_ADMIN")'
        ),
        new Post(
            uriTemplate: '/tenants/{id}/impersonate',
            status: 200,
            controller: 'App\Controller\TenantImpersonationController::start',
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'Start tenant impersonation'),
            security: 'is_granted("ROLE_SUPER_ADMIN")'
        ),
        new Post(
            uriTemplate: '/tenants/{id}/stop-impersonate',
            status: 200,
            controller: 'App\Controller\TenantImpersonationController::stop',
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'End tenant impersonation'),
            security: 'is_granted("ROLE_USER")'
        ),
        new Delete(
            uriTemplate: '/tenants/{id}',
            processor: TenantLifecycleProcessor::class,
            security: 'is_granted("ROLE_SUPER_ADMIN")'
        ),
    ],
    normalizationContext: ['groups' => ['tenant:read']],
    shortName: 'Tenant',
)]
final class Tenant
{
    #[Groups(['tenant:read', 'tenant:write'])]
    public ?string $id = null;

    #[Groups(['tenant:read', 'tenant:write'])]
    public ?string $name = null;

    #[Groups(['tenant:read', 'tenant:write'])]
    public ?string $code = null;

    #[Groups(['tenant:read', 'tenant:write'])]
    public ?string $domain = null;

    #[Groups(['tenant:read'])]
    public ?TenantStatus $status = null;

    #[Groups(['tenant:write'])]
    public ?string $adminEmail = null;

    #[Groups(['tenant:write'])]
    public ?string $adminName = null;

    #[Groups(['tenant:write'])]
    public ?string $adminPassword = null;

    #[Groups(['tenant:read', 'tenant:write'])]
    public ?TenantPlan $plan = TenantPlan::Starter;

    #[Groups(['tenant:read'])]
    public ?string $createdAt = null;
}
