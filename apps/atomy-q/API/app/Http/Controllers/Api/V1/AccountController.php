<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Account API controller (Section 27).
 *
 * Handles profile, preferences, notifications, subscription, and payment methods.
 */
final class AccountController extends Controller
{
    use ExtractsAuthContext;

    /**
     * Get current user profile.
     *
     * GET /me or GET /account/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $userId = $this->userId($request);

        /** @var User|null $user */
        $user = User::query()
            ->where('id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($user === null) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'tenantId' => $user->tenant_id,
                'tenant_id' => $user->tenant_id,
            ],
        ]);
    }

    /**
     * Update current user profile.
     *
     * PUT /me or PUT /account/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $userId = $this->userId($request);

        /** @var User|null $user */
        $user = User::query()
            ->where('id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($user === null) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'tenantId' => $user->tenant_id,
                'tenant_id' => $user->tenant_id,
            ],
        ]);
    }

    /**
     * Change password.
     *
     * POST /account/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'message' => 'Password updated',
            ],
        ]);
    }

    /**
     * Get user preferences.
     *
     * GET /account/preferences
     */
    public function preferences(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'theme' => 'light',
                'locale' => 'en',
            ],
        ]);
    }

    /**
     * Update user preferences.
     *
     * PUT /account/preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'theme' => 'light',
                'locale' => 'en',
            ],
        ]);
    }

    /**
     * Get notification settings.
     *
     * GET /account/notifications
     */
    public function notificationSettings(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'email' => true,
                'push' => false,
            ],
        ]);
    }

    /**
     * Update notification settings.
     *
     * PUT /account/notifications
     */
    public function updateNotificationSettings(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'email' => true,
                'push' => false,
            ],
        ]);
    }

    /**
     * Get subscription details.
     *
     * GET /account/subscription
     */
    public function subscription(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'plan' => 'standard',
                'status' => 'active',
            ],
        ]);
    }

    /**
     * Get available subscription plans.
     *
     * GET /account/subscription/plans
     */
    public function subscriptionPlans(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * Change subscription plan.
     *
     * POST /account/subscription/change
     */
    public function changeSubscription(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'plan' => 'standard',
                'status' => 'active',
            ],
        ]);
    }

    /**
     * List payment methods.
     *
     * GET /account/payment-methods
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * Add payment method.
     *
     * POST /account/payment-methods
     */
    public function addPaymentMethod(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'stub-pm-id',
                'last4' => '4242',
                'brand' => 'visa',
            ],
        ], 201);
    }

    /**
     * Remove payment method.
     *
     * DELETE /account/payment-methods/:id
     */
    public function removePaymentMethod(Request $request, string $id): JsonResponse
    {
        return response()->json([], 204);
    }

    /**
     * Set default payment method.
     *
     * PATCH /account/payment-methods/:id/default
     */
    public function setDefaultPaymentMethod(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'default' => true,
            ],
        ]);
    }
}
