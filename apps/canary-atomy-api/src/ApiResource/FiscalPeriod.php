<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\FiscalPeriodCollectionProvider;
use App\State\FiscalPeriodItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * FiscalPeriod API Resource.
 *
 * Exposes fiscal periods through the SettingsManagement orchestrator.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/fiscal-periods',
            normalizationContext: ['groups' => ['fiscal_period:read']],
            provider: FiscalPeriodCollectionProvider::class
        ),
        new Get(
            uriTemplate: '/fiscal-periods/{id}',
            normalizationContext: ['groups' => ['fiscal_period:read']],
            provider: FiscalPeriodItemProvider::class
        ),
        new Post(
            uriTemplate: '/fiscal-periods/{id}/open',
            status: 200,
            controller: 'App\Controller\FiscalPeriodController::open',
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'Open a fiscal period')
        ),
        new Post(
            uriTemplate: '/fiscal-periods/{id}/close',
            status: 200,
            controller: 'App\Controller\FiscalPeriodController::close',
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'Close a fiscal period')
        ),
    ],
    normalizationContext: ['groups' => ['fiscal_period:read']],
    shortName: 'FiscalPeriod',
)]
final class FiscalPeriod
{
    #[Groups(['fiscal_period:read'])]
    public ?string $id = null;

    #[Groups(['fiscal_period:read'])]
    public ?string $name = null;

    #[Groups(['fiscal_period:read'])]
    public ?string $startDate = null;

    #[Groups(['fiscal_period:read'])]
    public ?string $endDate = null;

    #[Groups(['fiscal_period:read'])]
    public ?string $status = null;

    #[Groups(['fiscal_period:read'])]
    public ?bool $isCurrent = null;
}
