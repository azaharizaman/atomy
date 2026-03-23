<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Contracts\JwtServiceInterface;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    protected string $tenantId = 'tenant-test';
    protected string $userId = 'user-test';

    /**
     * @return array<string, string>
     */
    protected function authHeaders(?string $tenantId = null, ?string $userId = null, ?string $idempotencyKey = null): array
    {
        $jwt = app(JwtServiceInterface::class);
        $token = $jwt->issueAccessToken($userId ?? $this->userId, $tenantId ?? $this->tenantId);

        return [
            'Authorization' => "Bearer {$token}",
            'Idempotency-Key' => $idempotencyKey ?? (string) Str::uuid(),
        ];
    }
}
