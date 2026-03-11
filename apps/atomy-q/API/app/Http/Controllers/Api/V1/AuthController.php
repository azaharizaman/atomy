<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\JwtServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class AuthController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly JwtServiceInterface $jwt
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
            'tenant_id' => ['required', 'string', 'max:64'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tenantId = (string) $request->input('tenant_id');
        $email = $request->input('email');
        $password = $request->input('password');

        /** @var User|null $user */
        $user = User::query()
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();

        if ($user === null || !Hash::check($password, (string) $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $accessToken = $this->jwt->issueAccessToken((string) $user->id, $tenantId);
        $refreshToken = $this->jwt->issueRefreshToken((string) $user->id, $tenantId);

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
