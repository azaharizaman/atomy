<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\TokenManagerInterface;
use Nexus\Identity\Contracts\UserAuthenticatorInterface;
use Nexus\Identity\Contracts\UserManagerInterface;
use Nexus\Identity\Services\TrustedDeviceManager;
use Nexus\Identity\ValueObjects\DeviceFingerprint;
use Nexus\Identity\Exceptions\InvalidCredentialsException;
use Nexus\Identity\Exceptions\AccountLockedException;
use Nexus\Identity\ValueObjects\Credentials;
use Nexus\Crypto\Contracts\HasherInterface;

final readonly class AuthenticationController
{
    public function __construct(
        private UserAuthenticatorInterface $authenticator,
        private UserManagerInterface $userManager,
        private SessionManagerInterface $sessionManager,
        private TokenManagerInterface $tokenManager,
        private TrustedDeviceManager $deviceManager,
        private HasherInterface $hasher
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $credentials = new Credentials(
                $validated['email'],
                $validated['password']
            );

            $user = $this->authenticator->authenticate($credentials);

            // Create device fingerprint
            $fingerprint = DeviceFingerprint::fromRequest([
                'user_agent' => $request->userAgent(),
                'accept_language' => $request->header('Accept-Language'),
                'accept_encoding' => $request->header('Accept-Encoding'),
            ], $this->hasher);

            // Check if device is recognized
            $isNewDevice = !$this->deviceManager->isDeviceRecognized(
                $user->getId(),
                $fingerprint->hash
            );

            // Register device if new (Trust On First Use)
            if ($isNewDevice) {
                $this->deviceManager->registerDevice(
                    userId: $user->getId(),
                    fingerprint: $fingerprint,
                    trustImmediately: true,
                    location: [] // Can be populated with Nexus\Geo integration
                );
            }

            // Create session token with device fingerprint
            $sessionToken = $this->sessionManager->createSession(
                $user->getId(),
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_fingerprint' => $fingerprint->hash,
                    'geographic_location' => [], // Can be populated with Nexus\Geo
                ]
            );

            return response()->json([
                'message' => 'Login successful',
                'session_token' => $sessionToken->token,
                'expires_at' => $sessionToken->expiresAt->format('c'),
                'device_fingerprint' => $fingerprint->hash,
                'is_new_device' => $isNewDevice,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                ],
            ]);
        } catch (InvalidCredentialsException $e) {
            return response()->json([
                'error' => 'Invalid credentials',
                'message' => $e->getMessage(),
            ], 401);
        } catch (AccountLockedException $e) {
            return response()->json([
                'error' => 'Account locked',
                'message' => $e->getMessage(),
            ], 423);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->header('X-Session-Token') ?? $request->bearerToken();

        if ($token) {
            $this->sessionManager->revokeSession($token);
        }

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    public function createToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'array',
            'scopes.*' => 'string',
            'expires_at' => 'nullable|date',
        ]);

        $user = $request->attributes->get('authenticated_user');

        $apiToken = $this->tokenManager->generateToken(
            $user->getId(),
            $validated['name'],
            $validated['scopes'] ?? [],
            isset($validated['expires_at']) ? new \DateTime($validated['expires_at']) : null
        );

        return response()->json([
            'message' => 'Token created successfully',
            'token' => $apiToken->token,
            'token_id' => $apiToken->id,
            'name' => $apiToken->name,
            'scopes' => $apiToken->scopes,
            'expires_at' => $apiToken->expiresAt?->format('c'),
        ], 201);
    }

    public function revokeToken(Request $request, string $tokenId): JsonResponse
    {
        $this->tokenManager->revokeToken($tokenId);

        return response()->json([
            'message' => 'Token revoked successfully',
        ]);
    }

    public function listTokens(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $tokens = $this->tokenManager->getUserTokens($user->getId());

        return response()->json([
            'tokens' => $tokens,
        ]);
    }

    public function listSessions(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $sessions = $this->sessionManager->getActiveSessions($user->getId());

        return response()->json([
            'sessions' => $sessions,
        ]);
    }

    public function revokeAllSessions(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $currentToken = $request->header('X-Session-Token');

        if ($currentToken) {
            $this->sessionManager->revokeOtherSessions($user->getId(), $currentToken);
        } else {
            $this->sessionManager->revokeAllSessions($user->getId());
        }

        return response()->json([
            'message' => 'Sessions revoked successfully',
        ]);
    }

    // ============================================
    // Device Management Endpoints
    // ============================================

    public function listDevices(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $devices = $this->deviceManager->getUserDevices($user->getId());

        return response()->json([
            'devices' => array_map(function ($device) {
                return [
                    'id' => $device->getId(),
                    'fingerprint' => $device->getFingerprint(),
                    'name' => $device->getDeviceName(),
                    'is_trusted' => $device->isTrusted(),
                    'trusted_at' => $device->getTrustedAt()?->format('c'),
                    'last_used_at' => $device->getLastUsedAt()?->format('c'),
                    'metadata' => $device->getMetadata(),
                ];
            }, $devices),
        ]);
    }

    public function revokeDevice(Request $request, string $fingerprint): JsonResponse
    {
        // Validate fingerprint format (64 hex characters for SHA-256)
        if (!$this->validateFingerprint($fingerprint)) {
            return response()->json([
                'error' => 'Invalid device fingerprint format',
            ], 400);
        }

        $user = $request->attributes->get('authenticated_user');

        // Find and revoke the device directly
        $device = $this->deviceManager->findByUserIdAndFingerprint($user->getId(), $fingerprint);
        
        if ($device === null) {
            return response()->json([
                'error' => 'Device not found',
            ], 404);
        }

        // Terminate all sessions for this device
        $this->sessionManager->terminateByDeviceId($user->getId(), $fingerprint);
        
        // Revoke the device
        $this->deviceManager->revokeDevice($device->getId());

        return response()->json([
            'message' => 'Device access revoked successfully',
        ]);
    }

    public function trustDevice(Request $request, string $fingerprint): JsonResponse
    {
        // Validate fingerprint format (64 hex characters for SHA-256)
        if (!$this->validateFingerprint($fingerprint)) {
            return response()->json([
                'error' => 'Invalid device fingerprint format',
            ], 400);
        }

        $user = $request->attributes->get('authenticated_user');

        // Find device and mark as trusted directly
        $device = $this->deviceManager->findByUserIdAndFingerprint($user->getId(), $fingerprint);
        
        if ($device === null) {
            return response()->json([
                'error' => 'Device not found',
            ], 404);
        }

        $this->deviceManager->trustDevice($device->getId());
        
        return response()->json([
            'message' => 'Device marked as trusted',
            'device' => [
                'id' => $device->getId(),
                'fingerprint' => $device->getFingerprint(),
                'name' => $device->getDeviceName(),
                'is_trusted' => true,
            ],
        ]);
    }

    /**
     * Validate device fingerprint format
     * 
     * @param string $fingerprint Device fingerprint to validate
     * @return bool True if valid (64 hex characters for SHA-256)
     */
    private function validateFingerprint(string $fingerprint): bool
    {
        return ctype_xdigit($fingerprint) && strlen($fingerprint) === 64;
    }
}
