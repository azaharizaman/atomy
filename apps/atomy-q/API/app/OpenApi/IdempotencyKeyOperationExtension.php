<?php

declare(strict_types=1);

namespace App\OpenApi;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

/**
 * Documents the Idempotency-Key header on Phase 1 POST routes that use the idempotency middleware.
 */
final class IdempotencyKeyOperationExtension extends OperationExtension
{
    /** @var list<string> */
    private const IDEMPOTENT_ROUTE_NAMES = [
        'v1.rfqs.store',
        'v1.rfqs.bulk-action',
        'v1.rfqs.duplicate',
        'v1.rfqs.invitations.store',
        'v1.rfqs.invitations.remind',
        'v1.rfq-templates.store',
        'v1.rfq-templates.duplicate',
        'v1.rfq-templates.apply',
        'v1.projects.store',
        'v1.tasks.store',
    ];

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $routeName = $routeInfo->route->getName();
        if ($routeName === null || ! in_array($routeName, self::IDEMPOTENT_ROUTE_NAMES, true)) {
            return;
        }

        if (strtolower($routeInfo->method) !== 'post') {
            return;
        }

        $string = new StringType();
        $string->setMax(256);

        $operation->addParameters([
            Parameter::make('Idempotency-Key', 'header')
                ->required(true)
                ->description(
                    'Required on idempotent POST operations. Non-empty string, max 256 characters. '
                    .'Same key + same request fingerprint replays the stored response; same key + different body returns 409 with `code` `idempotency_fingerprint_conflict`.'
                )
                ->setSchema(Schema::fromType($string))
                ->example('550e8400-e29b-41d4-a716-446655440000'),
        ]);
    }
}
