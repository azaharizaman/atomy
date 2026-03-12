<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use Illuminate\Http\Request;
use Tests\TestCase;

final class ExtractsAuthContextTest extends TestCase
{
    public function test_extracts_user_and_tenant_ids(): void
    {
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('auth_user_id', 'user-1');
        $request->attributes->set('auth_tenant_id', 'tenant-1');

        $subject = new class {
            use ExtractsAuthContext;

            public function getUserId(Request $request): string
            {
                return $this->userId($request);
            }

            public function getTenantId(Request $request): string
            {
                return $this->tenantId($request);
            }
        };

        $this->assertSame('user-1', $subject->getUserId($request));
        $this->assertSame('tenant-1', $subject->getTenantId($request));
    }

    public function test_pagination_params_are_clamped(): void
    {
        config([
            'atomy.pagination.default_per_page' => 25,
            'atomy.pagination.max_per_page' => 50,
        ]);

        $request = Request::create('/api/v1/test', 'GET', [
            'page' => 0,
            'per_page' => 500,
        ]);

        $subject = new class {
            use ExtractsAuthContext;

            /**
             * @return array{page: int, per_page: int}
             */
            public function getPagination(Request $request): array
            {
                return $this->paginationParams($request);
            }
        };

        $params = $subject->getPagination($request);

        $this->assertSame(1, $params['page']);
        $this->assertSame(50, $params['per_page']);
    }
}
