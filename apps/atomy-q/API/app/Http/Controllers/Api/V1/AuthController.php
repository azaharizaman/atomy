<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\JwtServiceInterface;
use App\Contracts\MfaChallengeStoreInterface;
use App\Contracts\PasswordResetServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use Nexus\Identity\Exceptions\AccountInactiveException;
use Nexus\Identity\Exceptions\AccountLockedException;
use Nexus\Identity\Exceptions\InvalidCredentialsException;
use Nexus\Identity\Contracts\MfaVerificationServiceInterface;
use Nexus\Identity\Contracts\UserQueryInterface as IdentityUserQueryInterface;
use Nexus\IdentityOperations\Contracts\UserAuthenticationCoordinatorInterface;
use Nexus\IdentityOperations\Services\AuditLoggerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Login and SSO use {@see UserAuthenticationCoordinatorInterface}; refresh still reissues the app JWTs
 * for the existing alpha contract.
 */
final class AuthController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly JwtServiceInterface $jwt,
        private readonly PasswordResetServiceInterface $passwordResetService,
        private readonly IdentityUserQueryInterface $identityUsers,
        private readonly UserAuthenticationCoordinatorInterface $authCoordinator,
        private readonly MfaChallengeStoreInterface $mfaChallenges,
        private readonly MfaVerificationServiceInterface $mfaVerification,
        private readonly AuditLoggerInterface $auditLogger,
    ) {
    }

    /**
     * Authenticate user with email and password, return JWT tokens.
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

        $user = $this->identityUsers->findByEmailOrNull($email);
        if ($user === null || $user->getTenantId() === null || trim($user->getTenantId()) === '') {
            $this->logAuditEvent('user.login.failure', sha1($email), [
                'email' => sha1($email),
                'reason' => 'invalid_credentials',
            ]);

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        try {
            $requiresMfa = $user->hasMfaEnabled();
            if (! $requiresMfa) {
                foreach ($this->identityUsers->getUserRoles($user->getId(), $user->getTenantId()) as $role) {
                    if ($role->requiresMfa()) {
                        $requiresMfa = true;
                        break;
                    }
                }
            }

            if ($requiresMfa) {
                $ctx = $this->authCoordinator->authenticateForMfaChallenge($email, $password, $user->getTenantId());

                $challengeId = $this->mfaChallenges->create(
                    (string) $ctx->userId,
                    (string) $ctx->tenantId,
                    'totp',
                );

                $this->logAuditEvent(
                    'user.mfa.challenge_issued',
                    (string) $ctx->userId,
                    [
                        'tenant_id' => (string) $ctx->tenantId,
                        'method' => 'totp',
                        'challenge_id' => $challengeId,
                    ]
                );

                return response()->json([
                    'message' => 'Multi-factor authentication required',
                    'challenge_id' => $challengeId,
                ], 401);
            }

            $ctx = $this->authCoordinator->authenticate($email, $password, $user->getTenantId());
            $sid = $ctx->sessionId ?? null;

            $this->logAuditEvent('user.login.success', (string) $ctx->userId, [
                'tenant_id' => (string) $ctx->tenantId,
                'session_id' => $sid,
            ]);

            return response()->json([
                'access_token' => $this->jwt->issueAccessToken(
                    (string) $ctx->userId,
                    (string) $ctx->tenantId,
                    $sid !== null ? ['sid' => $sid] : [],
                ),
                'refresh_token' => $this->jwt->issueRefreshToken((string) $ctx->userId, (string) $ctx->tenantId),
                'token_type' => 'Bearer',
                'expires_in' => $this->jwt->getTtlMinutes() * 60,
                'user' => [
                    'id' => $ctx->userId,
                    'email' => $ctx->email,
                    'name' => $ctx->firstName,
                    'role' => $ctx->roles[0] ?? null,
                    'tenantId' => $ctx->tenantId,
                ],
            ]);
        } catch (InvalidCredentialsException|AccountLockedException|AccountInactiveException $e) {
            $this->logAuditEvent('user.login.failure', (string) $user->getId(), [
                'tenant_id' => (string) $user->getTenantId(),
                'email' => sha1($email),
                'reason' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Invalid credentials'], 401);
        } catch (\Throwable) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
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

        try {
            if ($action === 'init') {
                $result = $this->authCoordinator->initiateSso($tenantId, $this->resolveTenantRedirectUri($tenantId));
                return response()->json(['data' => $result]);
            }

            $ctx = $this->authCoordinator->ssoCallback(
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

        $challengeId = (string) $request->input('challenge_id');
        $otp = trim((string) $request->input('otp'));

        $challenge = $this->mfaChallenges->find($challengeId);
        if (
            $challenge === null
            || $challenge->consumed_at !== null
            || $challenge->expires_at === null
            || $challenge->expires_at->isPast()
            || (string) $challenge->tenant_id === ''
        ) {
            $this->logAuditEvent('user.mfa.verification_failed', (string) ($challenge?->user_id ?? $challengeId), [
                'tenant_id' => $challenge !== null ? (string) $challenge->tenant_id : null,
                'challenge_id' => $challengeId,
                'reason' => 'invalid_or_expired_challenge',
            ]);

            return response()->json(['message' => 'Invalid or expired MFA challenge'], 401);
        }

        try {
            $challengeUser = $this->identityUsers->findById((string) $challenge->user_id);
        } catch (\Throwable) {
            $this->logAuditEvent('user.mfa.verification_failed', (string) $challenge->user_id, [
                'tenant_id' => (string) $challenge->tenant_id,
                'challenge_id' => $challengeId,
                'reason' => 'user_not_found',
            ]);

            return response()->json(['message' => 'Invalid or expired MFA challenge'], 401);
        }

        if (trim((string) $challengeUser->getTenantId()) === '' || $challengeUser->getTenantId() !== (string) $challenge->tenant_id) {
            $this->logAuditEvent('user.mfa.verification_failed', (string) $challenge->user_id, [
                'tenant_id' => (string) $challenge->tenant_id,
                'challenge_id' => $challengeId,
                'reason' => 'tenant_mismatch',
            ]);

            return response()->json(['message' => 'Invalid or expired MFA challenge'], 401);
        }

        $verified = false;

        try {
            $verified = $this->mfaVerification->verifyTotp((string) $challenge->user_id, $otp);
        } catch (\Throwable) {
            $verified = false;
        }

        if (! $verified) {
            try {
                $verified = $this->mfaVerification->verifyBackupCode((string) $challenge->user_id, $otp);
            } catch (\Throwable) {
                $verified = false;
            }
        }

        if (! $verified) {
            $this->mfaChallenges->incrementAttempts($challengeId);
            $this->logAuditEvent('user.mfa.verification_failed', (string) $challenge->user_id, [
                'tenant_id' => (string) $challenge->tenant_id,
                'challenge_id' => $challengeId,
            ]);
            return response()->json(['message' => 'Invalid MFA code'], 401);
        }

        $this->mfaChallenges->consume($challengeId);
        $this->logAuditEvent('user.mfa.verified', (string) $challenge->user_id, [
            'tenant_id' => (string) $challenge->tenant_id,
            'challenge_id' => $challengeId,
        ]);

        $ctx = $this->authCoordinator->completeMfaLogin((string) $challenge->user_id, (string) $challenge->tenant_id);
        $sid = $ctx->sessionId ?? null;

        $this->logAuditEvent('user.login.success', (string) $ctx->userId, [
            'tenant_id' => (string) $ctx->tenantId,
            'session_id' => $sid,
            'via_mfa' => true,
            'challenge_id' => $challengeId,
        ]);

        return response()->json([
            'access_token' => $this->jwt->issueAccessToken(
                (string) $ctx->userId,
                (string) $ctx->tenantId,
                $sid !== null ? ['sid' => $sid] : [],
            ),
            'refresh_token' => $this->jwt->issueRefreshToken((string) $ctx->userId, (string) $ctx->tenantId),
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->getTtlMinutes() * 60,
            'user' => [
                'id' => $ctx->userId,
                'email' => $ctx->email,
                'name' => $ctx->firstName,
                'role' => $ctx->roles[0] ?? null,
                'tenantId' => $ctx->tenantId,
            ],
        ]);
    }

    /**
     * Request password reset.
     *
     * POST /auth/forgot-password — body: `email` only. Reset tokens are stored per user tenant (from the user row).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->passwordResetService->sendResetLink((string) $request->input('email'));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => 'If an account exists for this email, password reset instructions have been sent.',
        ]);
    }

    /**
     * Complete password reset using email + token from the forgot-password mail.
     *
     * POST /auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
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
            $this->passwordResetService->resetPassword(
                (string) $request->input('email'),
                (string) $request->input('token'),
                (string) $request->input('password'),
            );
        } catch (\InvalidArgumentException) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to complete password reset. Please try again later.',
            ], 500);
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
        $userId = (string) $request->attributes->get('auth_user_id', '');
        $tenantId = (string) $request->attributes->get('auth_tenant_id', '');
        $sessionId = $request->attributes->get('auth_session_id');

        if ($userId === '' || $tenantId === '') {
            return response()->json([
                'message' => 'Logout flow is not implemented yet.',
            ], 501);
        }

        $loggedOut = $this->authCoordinator->logout(
            $userId,
            is_string($sessionId) && $sessionId !== '' ? $sessionId : null,
            $tenantId,
        );

        if (! $loggedOut) {
            return response()->json([
                'message' => 'Logout failed.',
            ], 500);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Best-effort audit logging. Auth responses should not fail because the audit sink is unavailable.
     *
     * @param array<string, mixed> $properties
     */
    private function logAuditEvent(string $event, string $subjectId, array $properties): void
    {
        try {
            $this->auditLogger->log($event, $subjectId, $properties);
        } catch (\Throwable $e) {
            report($e);
        }
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
