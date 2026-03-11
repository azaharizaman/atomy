<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * User API controller (Section 23).
 *
 * Handles users, invitations, roles, delegation rules, and authority limits.
 */
final class UserController extends Controller
{
    use ExtractsAuthContext;

    /**
     * List users.
     *
     * GET /users
     */
    public function index(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => 0,
            ],
        ]);
    }

    /**
     * Invite a new user.
     *
     * POST /users/invite
     */
    public function invite(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'stub-user-id',
                'email' => 'invited@example.com',
                'status' => 'pending',
            ],
        ], 201);
    }

    /**
     * Show a single user.
     *
     * GET /users/:id
     */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'email' => 'user@example.com',
                'name' => 'Stub User',
            ],
        ]);
    }

    /**
     * Update user.
     *
     * PUT /users/:id
     */
    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'email' => 'user@example.com',
                'name' => 'Stub User',
            ],
        ]);
    }

    /**
     * Suspend user.
     *
     * POST /users/:id/suspend
     */
    public function suspend(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'suspended',
            ],
        ]);
    }

    /**
     * Reactivate user.
     *
     * POST /users/:id/reactivate
     */
    public function reactivate(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'active',
            ],
        ]);
    }

    /**
     * List roles.
     *
     * GET /roles
     */
    public function roles(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * Get user delegation rules.
     *
     * GET /users/:id/delegation-rules
     */
    public function delegationRules(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'user_id' => $id,
                'rules' => [],
            ],
        ]);
    }

    /**
     * Update user delegation rules.
     *
     * PUT /users/:id/delegation-rules
     */
    public function updateDelegationRules(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'user_id' => $id,
                'rules' => [],
            ],
        ]);
    }

    /**
     * Update user authority limits.
     *
     * PUT /users/:id/authority-limits
     */
    public function updateAuthorityLimits(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'user_id' => $id,
                'limits' => [],
            ],
        ]);
    }
}
