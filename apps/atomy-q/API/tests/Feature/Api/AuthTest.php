<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Services\JwtService;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    public function test_login_validation_error(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors']);
    }

    public function test_login_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Invalid credentials']);
    }

    public function test_login_success(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@atomy.test',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
        ]);
    }

    public function test_refresh_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_valid_token(): void
    {
        $jwt = app(JwtService::class);
        $refreshToken = $jwt->issueRefreshToken('user-1', 'tenant-1');

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
        ]);
    }

    public function test_supporting_auth_endpoints(): void
    {
        $this->postJson('/api/v1/auth/sso')->assertOk()->assertJsonStructure(['redirect_url']);
        $this->postJson('/api/v1/auth/mfa/verify')->assertOk()->assertJsonStructure(['message', 'verified']);
        $this->postJson('/api/v1/auth/forgot-password')->assertOk()->assertJsonStructure(['message']);
        $this->postJson('/api/v1/auth/logout')->assertOk()->assertJsonStructure(['message']);
        $this->postJson('/api/v1/auth/device-trust')->assertOk()->assertJsonStructure(['message']);
    }
}
