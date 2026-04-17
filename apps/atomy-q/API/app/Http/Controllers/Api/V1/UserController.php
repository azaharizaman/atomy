<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;

/**
 * User API controller (Section 23).
 *
 * Handles users, invitations, roles, delegation rules, and authority limits.
 */
final class UserController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly UserQueryInterface $users,
        private readonly UserPersistInterface $userPersist,
        private readonly RoleQueryInterface $roles,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * List users.
     *
     * GET /users
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $criteria = ['tenant_id' => $tenantId];
        $status = $request->query('status');
        if (is_string($status) && trim($status) !== '') {
            $criteria['status'] = trim($status);
        }

        $userList = $this->users->search($criteria);
        $userIds = array_map(
            static fn (UserInterface $user): string => $user->getId(),
            $userList,
        );
        $rolesByUserId = $this->getRolesByUserIds($userIds, $tenantId);

        $users = array_map(
            fn (UserInterface $user): array => $this->serializeUser($user, $tenantId, $rolesByUserId),
            $userList,
        );

        $params = $this->paginationParams($request);
        $total = count($users);
        $offset = ($params['page'] - 1) * $params['per_page'];

        return response()->json([
            'data' => array_slice($users, $offset, $params['per_page']),
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => $total,
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
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        if ($this->users->emailExists($email, null, $tenantId)) {
            return response()->json([
                'message' => 'A user with that email already exists.',
            ], 409);
        }

        $created = $this->userPersist->create([
            'tenant_id' => $tenantId,
            'email' => $email,
            'password_hash' => $this->passwordHasher->hash(Str::random(32)),
            'name' => trim((string) $validated['name']),
            'role' => 'user',
            'status' => 'pending_activation',
            'email_verified_at' => null,
        ]);

        return response()->json([
            'data' => $this->serializeUser($created, $tenantId),
        ], 201);
    }

    /**
     * Show a single user.
     *
     * GET /users/:id
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $user = $this->assertTenantUserExists($id, $tenantId);

        return response()->json([
            'data' => $this->serializeUser($user, $tenantId),
        ]);
    }

    /**
     * Update user.
     *
     * PUT /users/:id
     */
    public function update(Request $request, string $id): JsonResponse
    {
        return $this->deferred('update user', $request, [
            'user_id' => $id,
        ]);
    }

    /**
     * Suspend user.
     *
     * POST /users/:id/suspend
     */
    public function suspend(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $this->assertTenantUserExists($id, $tenantId);
        $user = $this->userPersist->update($id, [
            'status' => 'suspended',
        ]);

        return response()->json([
            'data' => $this->serializeUser($user, $tenantId),
        ]);
    }

    /**
     * Reactivate user.
     *
     * POST /users/:id/reactivate
     */
    public function reactivate(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $this->assertTenantUserExists($id, $tenantId);
        $user = $this->userPersist->update($id, [
            'status' => 'active',
        ]);

        return response()->json([
            'data' => $this->serializeUser($user, $tenantId),
        ]);
    }

    /**
     * List roles.
     *
     * GET /roles
     */
    public function roles(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => array_map(
                static fn (RoleInterface $role): array => [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'description' => $role->getDescription(),
                    'tenant_id' => $role->getTenantId(),
                    'is_system_role' => $role->isSystemRole(),
                ],
                $this->roles->getAll($tenantId),
            ),
        ]);
    }

    /**
     * Get user delegation rules.
     *
     * GET /users/:id/delegation-rules
     */
    public function delegationRules(Request $request, string $id): JsonResponse
    {
        return $this->deferred('get delegation rules', $request, [
            'user_id' => $id,
        ]);
    }

    /**
     * Update user delegation rules.
     *
     * PUT /users/:id/delegation-rules
     */
    public function updateDelegationRules(Request $request, string $id): JsonResponse
    {
        return $this->deferred('update delegation rules', $request, [
            'user_id' => $id,
        ]);
    }

    /**
     * Update user authority limits.
     *
     * PUT /users/:id/authority-limits
     */
    public function updateAuthorityLimits(Request $request, string $id): JsonResponse
    {
        return $this->deferred('update authority limits', $request, [
            'user_id' => $id,
        ]);
    }

    private function assertTenantUserExists(string $id, string $tenantId): UserInterface
    {
        try {
            return $this->users->findById($id, $tenantId);
        } catch (UserNotFoundException) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(UserInterface $user, string $tenantId, array $rolesByUserId = []): array
    {
        $userId = $user->getId();
        $roles = $rolesByUserId[$userId] ?? $this->users->getUserRoles($userId, $tenantId);
        $primaryRole = 'user';
        if ($roles !== []) {
            $roleName = trim((string) $roles[0]->getName());
            if ($roleName !== '') {
                $primaryRole = $roleName;
            }
        }

        $metadata = $user->getMetadata() ?? [];
        $lastLoginAt = $metadata['last_login_at'] ?? null;
        if ($lastLoginAt instanceof \DateTimeInterface) {
            $lastLoginAt = $lastLoginAt->format(\DateTimeInterface::ATOM);
        } elseif (! is_string($lastLoginAt)) {
            $lastLoginAt = null;
        }

        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'status' => $user->getStatus(),
            'role' => $primaryRole,
            'tenant_id' => $user->getTenantId(),
            'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'last_login_at' => $lastLoginAt,
        ];
    }

    /**
     * @param string[] $userIds
     * @return array<string, RoleInterface[]>
     */
    private function getRolesByUserIds(array $userIds, string $tenantId): array
    {
        $rolesByUserId = [];
        foreach ($userIds as $userId) {
            $rolesByUserId[$userId] = $this->users->getUserRoles($userId, $tenantId);
        }

        return $rolesByUserId;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function deferred(string $operation, Request $request, array $context = []): JsonResponse
    {
        return response()->json([
            'error' => 'Not implemented',
            'message' => $operation . ' is not implemented in alpha.',
            'context' => array_merge([
                'tenant_id' => $this->tenantId($request),
            ], $context),
        ], 501);
    }
}
