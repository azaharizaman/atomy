<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\Request;

trait ExtractsAuthContext
{
    protected function userId(Request $request): string
    {
        return (string) $request->attributes->get('auth_user_id');
    }

    protected function tenantId(Request $request): string
    {
        return (string) $request->attributes->get('auth_tenant_id');
    }

    protected function paginationParams(Request $request): array
    {
        $perPage = min(
            (int) $request->query('per_page', (string) config('atomy.pagination.default_per_page')),
            (int) config('atomy.pagination.max_per_page'),
        );

        return [
            'page' => max(1, (int) $request->query('page', '1')),
            'per_page' => max(1, $perPage),
        ];
    }
}
