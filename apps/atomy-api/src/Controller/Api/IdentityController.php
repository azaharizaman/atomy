<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Nexus\Domain\Identity\Contracts\UserRepositoryInterface;
use Nexus\Domain\Identity\Contracts\PasswordHasherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;

#[Route('/api/users')]
final class IdentityController
{
    public function __construct(
        private readonly UserRepositoryInterface $repo,
        private readonly PasswordHasherInterface $hasher
    ) {}

    private function serializeUser(object $user): array
    {
        // Minimal serializer for Nexus\Domain\Identity\Contracts\UserInterface
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'status' => $user->getStatus(),
            'name' => $user->getName(),
            'tenant_id' => $user->getTenantId(),
            'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
            'created_at' => $user->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $user->getUpdatedAt()->format(DATE_ATOM),
            'email_verified_at' => $user->getEmailVerifiedAt()?->format(DATE_ATOM),
            'password_changed_at' => $user->getPasswordChangedAt()?->format(DATE_ATOM),
        ];
    }

    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $criteria = $request->query->all();

        // If no criteria, return all users via search with empty criteria
        $users = $this->repo->search($criteria ?: []);

        $result = array_map(fn($u) => $this->serializeUser($u), $users);

        return new JsonResponse($result);
    }

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'email and password required'], 400);
        }

        if ($this->repo->emailExists($email)) {
            return new JsonResponse(['error' => 'email already exists'], 409);
        }

        $hashed = $this->hasher->hash($password);

        $user = $this->repo->create([
            'id' => (string) new Ulid(),
            'email' => $email,
            'password_hash' => $hashed,
            'name' => $data['name'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'roles' => $data['roles'] ?? ['ROLE_USER'],
        ]);

        return new JsonResponse($this->serializeUser($user), 201);
    }

    

    

    

    

    #[Route('/{id}', name: 'api_users_get', methods: ['GET'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function get(string $id): JsonResponse
    {
        try {
            $user = $this->repo->findById($id);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        return new JsonResponse($this->serializeUser($user));
    }

    #[Route('/by-email', name: 'api_users_by_email', methods: ['GET'])]
    public function byEmail(Request $request): JsonResponse
    {
        $email = $request->query->get('email');
        if (!$email) {
            return new JsonResponse(['error' => 'email required'], 400);
        }

        $user = $this->repo->findByEmailOrNull($email);
        if ($user === null) {
            return new JsonResponse(null, 204);
        }

        return new JsonResponse($this->serializeUser($user));
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT','PATCH'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $user = $this->repo->findById($id);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        $data = json_decode($request->getContent() ?: '{}', true);

        if (isset($data['password'])) {
            $data['password_hash'] = $this->hasher->hash($data['password']);
            unset($data['password']);
        }

        $updated = $this->repo->update($id, $data);

        return new JsonResponse($this->serializeUser($updated));
    }

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'], requirements: ['id' => '[0-9A-Za-z]{26}'])]
    public function delete(string $id): JsonResponse
    {
        $ok = $this->repo->delete($id);
        return new JsonResponse(['ok' => $ok], $ok ? 200 : 404);
    }

    #[Route('/{id}/roles', name: 'api_users_assign_role', methods: ['POST'])]
    public function assignRole(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $role = $data['role'] ?? null;
        if (!$role) {
            return new JsonResponse(['error' => 'role required'], 400);
        }

        $this->repo->assignRole($id, $role);
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}/roles/{role}', name: 'api_users_revoke_role', methods: ['DELETE'])]
    public function revokeRole(string $id, string $role): JsonResponse
    {
        $this->repo->revokeRole($id, $role);
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}/permissions', name: 'api_users_assign_permission', methods: ['POST'])]
    public function assignPermission(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $perm = $data['permission'] ?? null;
        if (!$perm) {
            return new JsonResponse(['error' => 'permission required'], 400);
        }

        $this->repo->assignPermission($id, $perm);
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}/permissions/{permission}', name: 'api_users_revoke_permission', methods: ['DELETE'])]
    public function revokePermission(string $id, string $permission): JsonResponse
    {
        $this->repo->revokePermission($id, $permission);
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/status/{status}', name: 'api_users_by_status', methods: ['GET'])]
    public function byStatus(string $status): JsonResponse
    {
        $users = $this->repo->findByStatus($status);
        return new JsonResponse(array_map(fn($u) => $this->serializeUser($u), $users));
    }

    #[Route('/role/{roleId}', name: 'api_users_by_role', methods: ['GET'])]
    public function byRole(string $roleId): JsonResponse
    {
        $users = $this->repo->findByRole($roleId);
        return new JsonResponse(array_map(fn($u) => $this->serializeUser($u), $users));
    }

    #[Route('/{id}/lock', name: 'api_users_lock', methods: ['POST'])]
    public function lock(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $reason = $data['reason'] ?? 'locked via API';
        $this->repo->lockAccount($id, $reason);
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}/unlock', name: 'api_users_unlock', methods: ['POST'])]
    public function unlock(string $id): JsonResponse
    {
        $this->repo->unlockAccount($id);
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}/increment-failed', name: 'api_users_inc_failed', methods: ['POST'])]
    public function incrementFailed(string $id): JsonResponse
    {
        $value = $this->repo->incrementFailedLoginAttempts($id);
        return new JsonResponse(['failed_attempts' => $value]);
    }

    #[Route('/{id}/reset-failed', name: 'api_users_reset_failed', methods: ['POST'])]
    public function resetFailed(string $id): JsonResponse
    {
        $this->repo->resetFailedLoginAttempts($id);
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}/last-login', name: 'api_users_last_login', methods: ['POST'])]
    public function lastLogin(string $id): JsonResponse
    {
        $this->repo->updateLastLogin($id);
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/search', name: 'api_users_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $criteria = $request->query->all();
        $users = $this->repo->search($criteria);
        return new JsonResponse(array_map(fn($u) => $this->serializeUser($u), $users));
    }
}
