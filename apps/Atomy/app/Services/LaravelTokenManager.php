<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiToken as ApiTokenModel;
use App\Models\User;
use Nexus\Identity\Contracts\TokenManagerInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\ValueObjects\ApiToken;
use Nexus\Identity\Exceptions\InvalidTokenException;
use Illuminate\Support\Str;

/**
 * Laravel token manager implementation
 */
final readonly class LaravelTokenManager implements TokenManagerInterface
{
    public function generateToken(
        string $userId,
        string $name,
        array $scopes = [],
        ?\DateTimeInterface $expiresAt = null
    ): ApiToken {
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);

        $apiToken = ApiTokenModel::create([
            'user_id' => $userId,
            'name' => $name,
            'token_hash' => $tokenHash,
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
        ]);

        return new ApiToken(
            id: $apiToken->id,
            token: $token,
            userId: $userId,
            name: $name,
            scopes: $scopes,
            expiresAt: $expiresAt
        );
    }

    public function validateToken(string $token): UserInterface
    {
        $tokenHash = hash('sha256', $token);

        $apiToken = ApiTokenModel::where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->first();

        if (!$apiToken || !$apiToken->isValid()) {
            throw new InvalidTokenException('Invalid or expired token');
        }

        // Update last used timestamp
        $apiToken->update(['last_used_at' => now()]);

        return User::findOrFail($apiToken->user_id);
    }

    public function isValid(string $token): bool
    {
        try {
            $this->validateToken($token);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function revokeToken(string $tokenId): void
    {
        ApiTokenModel::where('id', $tokenId)
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllTokens(string $userId): void
    {
        ApiTokenModel::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function getUserTokens(string $userId): array
    {
        return ApiTokenModel::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'scopes' => $token->scopes,
                    'expires_at' => $token->expires_at?->toIso8601String(),
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'created_at' => $token->created_at->toIso8601String(),
                ];
            })
            ->all();
    }

    public function getTokenScopes(string $token): array
    {
        $tokenHash = hash('sha256', $token);

        $apiToken = ApiTokenModel::where('token_hash', $tokenHash)->first();

        if (!$apiToken) {
            return [];
        }

        return $apiToken->scopes;
    }

    public function cleanupExpiredTokens(): int
    {
        return ApiTokenModel::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
