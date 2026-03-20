<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\JwtServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Models\User;
use App\Services\Auth\PasswordResetService;
use Nexus\IdentityOperations\Contracts\UserAuthenticationCoordinatorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Login and refresh use Eloquent + {@see JwtServiceInterface} only.
 * SSO resolves {@see UserAuthenticationCoordinatorInterface} lazily; that path requires Nexus Identity
 * container bindings (e.g. {@see \Nexus\Identity\Contracts\UserPersistInterface}).
 */
final class AuthController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly JwtServiceInterface $jwt,
    ) {
    }

    /**
     * Authenticate user with email and password, return JWT tokens.
     *
     * Tenant is taken from the user row (one user → one tenant). `tenant_id` in the body is ignored.
     *
     * POST /auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || !Hash::check($password, (string) $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $tenantId = (string) $user->tenant_id;

        $accessToken = $this->jwt->issueAccessToken((string) $user->id, $tenantId);
        $refreshToken = $this->jwt->issueRefreshToken((string) $user->id, $tenantId);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->getTtlMinutes() * 60,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'tenantId' => $user->tenant_id,
            ],
        ]);
    }

    /**
     * Initiate SSO flow, return redirect URL.
     *
     * POST /auth/sso
     */
    public function sso(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', 'string', 'in:init,callback'],
            'tenant_id' => ['required', 'string', 'max:64'],
            'code' => ['required_if:action,callback', 'string'],
            'state' => ['required_if:action,callback', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $action = (string) $request->input('action');
        $tenantId = (string) $request->input('tenant_id');

        /** @var UserAuthenticationCoordinatorInterface $identityOps */
        $identityOps = app(UserAuthenticationCoordinatorInterface::class);

        try {
            if ($action === 'init') {
                $result = $identityOps->initiateSso($tenantId, $this->resolveTenantRedirectUri($tenantId));
                return response()->json(['data' => $result]);
            }

            $ctx = $identityOps->ssoCallback(
                tenantId: $tenantId,
                code: (string) $request->input('code'),
                state: (string) $request->input('state'),
            );

            $userId = (string) ($ctx->userId ?? '');
            if ($userId === '') {
                return response()->json(['message' => 'SSO authentication failed'], 401);
            }

            $accessToken = $this->jwt->issueAccessToken($userId, $tenantId);
            $refreshToken = $this->jwt->issueRefreshToken($userId, $tenantId);

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->jwt->getTtlMinutes() * 60,
                'user' => [
                    'id' => $ctx->userId,
                    'email' => $ctx->email,
                    'name' => $ctx->firstName,
                    'role' => null,
                    'tenantId' => $tenantId,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException || $e instanceof \Error) {
                report($e);
                return response()->json(['message' => 'SSO provider unavailable'], 500);
            }
            return response()->json(['message' => 'SSO authentication failed'], 401);
        }
    }

    private function resolveTenantRedirectUri(string $tenantId): string
    {
        $tenantScoped = config('services.oidc.tenant_redirect_uris');
        if (is_array($tenantScoped)) {
            $candidate = $tenantScoped[$tenantId] ?? null;
            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        $fallback = config('services.oidc.redirect_uri');
        if (is_string($fallback) && trim($fallback) !== '') {
            return $fallback;
        }

        throw new \RuntimeException('OIDC redirect URI not configured');
    }

    /**
     * Verify MFA OTP code.
     *
     * POST /auth/mfa/verify
     */
    public function mfaVerify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'challenge_id' => ['required', 'string'],
            'otp' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json([
            'message' => 'MFA verification flow is not implemented yet.',
            'verified' => false,
        ], 501);
    }

    /**
     * Request password reset.
     *
     * POST /auth/forgot-password — body: `email` only (tenant resolved when flow is implemented).
     */
    public function forgotPassword(Request $request, PasswordResetService $passwordReset): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $passwordReset->sendResetLink((string) $request->input('email'));

        return response()->json([
            'message' => 'If an account exists for this email, password reset instructions have been sent.',
        ]);
    }

    /**
     * Complete password reset using email + token from the forgot-password mail.
     *
     * POST /auth/reset-password
     */
    public function resetPassword(Request $request, PasswordResetService $passwordReset): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'token' => ['required', 'string', 'min:10'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $passwordReset->resetPassword(
                (string) $request->input('email'),
                (string) $request->input('token'),
                (string) $request->input('password'),
            );
        } catch (\InvalidArgumentException) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        return response()->json(['message' => 'Password has been reset.']);
    }

    /**
     * Refresh access token using valid refresh token.
     *
     * POST /auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $refreshToken = $request->input('refresh_token');

        try {
            $payload = $this->jwt->decode($refreshToken);
        } catch (\Throwable) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        if ($payload->type !== 'refresh') {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $userId = $payload->sub;
        $tenantId = $payload->tenant_id;

        if ($userId === '' || $tenantId === '') {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $newAccessToken = $this->jwt->issueAccessToken($userId, $tenantId);
        $newRefreshToken = $this->jwt->issueRefreshToken($userId, $tenantId);

        return response()->json([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->getTtlMinutes() * 60,
        ]);
    }

    /**
     * Log out the current user.
     *
     * POST /auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Logout flow is not implemented yet.',
        ], 501);
    }

    /**
     * Trust the current device for simplified future authentication.
     *
     * POST /auth/device-trust
     */
    public function deviceTrust(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Device trust flow is not implemented yet.',
        ], 501);
    }
}
