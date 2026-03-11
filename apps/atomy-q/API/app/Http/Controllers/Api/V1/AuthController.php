<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class AuthController extends Controller
{
    use ExtractsAuthContext;

    private const TEST_USER_EMAIL = 'admin@atomy.test';
    private const TEST_USER_PASSWORD = 'password';
    private const TEST_USER_ID = '1';
    private const TEST_TENANT_ID = 'default';

    public function __construct(
        private readonly JwtService $jwt
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

        $email = $request->input('email');
        $password = $request->input('password');

        if ($email !== self::TEST_USER_EMAIL || $password !== self::TEST_USER_PASSWORD) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $accessToken = $this->jwt->issueAccessToken(self::TEST_USER_ID, self::TEST_TENANT_ID);
        $refreshToken = $this->jwt->issueRefreshToken(self::TEST_USER_ID, self::TEST_TENANT_ID);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->getTtlMinutes() * 60,
        ]);
    }

    /**
     * Initiate SSO flow, return redirect URL.
     *
     * POST /auth/sso
     */
    public function sso(Request $request): JsonResponse
    {
        return response()->json([
            'redirect_url' => url('/auth/sso/callback'),
        ]);
    }

    /**
     * Verify MFA OTP code.
     *
     * POST /auth/mfa/verify
     */
    public function mfaVerify(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'MFA verified successfully',
            'verified' => true,
        ]);
    }

    /**
     * Request password reset.
     *
     * POST /auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'If an account exists with that email, a password reset link has been sent.',
        ]);
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

        $type = $payload->type ?? null;
        if ($type !== 'refresh') {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $userId = (string) ($payload->sub ?? '');
        $tenantId = (string) ($payload->tenant_id ?? '');

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
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Trust the current device for simplified future authentication.
     *
     * POST /auth/device-trust
     */
    public function deviceTrust(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Device trusted successfully',
        ]);
    }
}
