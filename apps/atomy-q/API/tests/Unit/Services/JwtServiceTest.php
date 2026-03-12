<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\JwtService;
use Tests\TestCase;

final class JwtServiceTest extends TestCase
{
    public function test_issue_and_decode_access_token(): void
    {
        $jwt = $this->buildJwtService();

        $token = $jwt->issueAccessToken('user-123', 'tenant-123');
        $payload = $jwt->decode($token);

        $this->assertSame('user-123', $payload->sub);
        $this->assertSame('tenant-123', $payload->tenant_id);
        $this->assertSame('access', $payload->type);
        $this->assertSame('atomy-q-test', $payload->iss);
    }

    public function test_issue_refresh_token(): void
    {
        $jwt = $this->buildJwtService();

        $token = $jwt->issueRefreshToken('user-456', 'tenant-456');
        $payload = $jwt->decode($token);

        $this->assertSame('user-456', $payload->sub);
        $this->assertSame('tenant-456', $payload->tenant_id);
        $this->assertSame('refresh', $payload->type);
    }

    public function test_get_ttl_minutes(): void
    {
        $jwt = $this->buildJwtService();

        $this->assertSame(60, $jwt->getTtlMinutes());
    }

    private function buildJwtService(): JwtService
    {
        return new JwtService(
            'test-jwt-secret',
            60,
            120,
            'HS256',
            'atomy-q-test'
        );
    }
}
