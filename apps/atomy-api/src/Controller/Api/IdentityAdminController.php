<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\IdentityAuditService;
use App\Security\Attribute\RequiresPermission;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Nexus\Identity\Contracts\PasswordValidatorInterface;
use Nexus\Identity\Contracts\TokenManagerInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\MfaVerifierInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Ulid;

/**
 * Admin-level Identity API.
 *
 * This controller exposes endpoints that map to the full set of Nexus\Identity services.
 * All endpoints produce audit events via IdentityAuditService.
 */
#[Route('/api/identity')]
final class IdentityAdminController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly PasswordHasherInterface $hasher,
        private readonly TokenManagerInterface $tokenManager,
        private readonly SessionManagerInterface $sessionManager,
        private readonly RoleRepositoryInterface $roleRepo,
        private readonly PermissionRepositoryInterface $permissionRepo,
        private readonly PasswordValidatorInterface $passwordValidator,
        private readonly MfaVerifierInterface $mfaVerifier,
        private readonly IdentityAuditService $auditService,
        private readonly AuditLogRepositoryInterface $auditLogRepo,
    ) {
    }

    // ------------------ User lifecycle & password ------------------

    #[Route('/register', name: 'iam_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'email and password required'], 400);
        }

        if ($this->userRepo->emailExists($email)) {
            return new JsonResponse(['error' => 'email exists'], 409);
        }

        // Validate password
        $errors = $this->passwordValidator->validate($password);
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        $hash = $this->hasher->hash($password);
        $user = $this->userRepo->create([
            'id' => (string) new Ulid(),
            'email' => $email,
            'password_hash' => $hash,
            'roles' => $data['roles'] ?? ['ROLE_USER'],
            'name' => $data['name'] ?? null,
        ]);

        // Audit: User created
        $this->auditService->logUserCreated($user->getId(), $email);

        return new JsonResponse(['id' => $user->getId(), 'email' => $user->getEmail()], 201);
    }

    #[Route('/users/{id}/change-password', name: 'iam_change_password', methods: ['POST'])]
    #[RequiresPermission('user', 'update')]
    public function changePassword(string $id, Request $request): JsonResponse
    {
        $new = json_decode($request->getContent() ?: '{}', true)['password'] ?? null;
        if (!$new) {
            return new JsonResponse(['error' => 'password required'], 400);
        }

        // Validate password
        $errors = $this->passwordValidator->validate($new);
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        // Hash and store
        $hash = $this->hasher->hash($new);
        try {
            $this->userRepo->update($id, ['password_hash' => $hash, 'password_changed_at' => new \DateTimeImmutable()]);
            $this->userRepo->resetFailedLoginAttempts($id);
            
            // Audit: Password changed
            $this->auditService->logPasswordChanged($id);
            
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/users/{id}/export', name: 'iam_export_user', methods: ['GET'])]
    #[RequiresPermission('user', 'read')]
    public function exportUser(string $id): JsonResponse
    {
        // Export comprehensive user data for GDPR right of access
        try {
            $user = $this->userRepo->findById($id);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        // Audit: Data export requested
        $this->auditService->logDataExport($id);

        // Core user data
        $payload = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
            'metadata' => method_exists($user, 'getMetadata') ? $user->getMetadata() : null,
            'created_at' => $user->getCreatedAt()->format(DATE_ATOM),
        ];

        // Include active sessions (already returns array of associative arrays)
        try {
            $sessions = $this->sessionManager->getActiveSessions($id);
            $payload['sessions'] = array_map(fn($s) => [
                'id' => $s['id'],
                'ip_address' => $s['metadata']['ip_address'] ?? null,
                'user_agent' => $s['metadata']['user_agent'] ?? null,
                'device_fingerprint' => $s['device_fingerprint'] ?? null,
                'created_at' => $s['created_at'],
                'last_activity_at' => $s['last_activity_at'],
                'expires_at' => $s['expires_at'],
            ], $sessions);
        } catch (\Throwable $e) {
            $payload['sessions'] = [];
        }

        // Include active tokens (already returns array of associative arrays)
        try {
            $tokens = $this->tokenManager->getUserTokens($id);
            // Filter out revoked tokens for GDPR export
            $activeTokens = array_filter($tokens, fn($t) => !($t['is_revoked'] ?? false));
            $payload['tokens'] = array_map(fn($t) => [
                'id' => $t['id'],
                'name' => $t['name'],
                'scopes' => $t['scopes'],
                'created_at' => $t['created_at'],
                'expires_at' => $t['expires_at'],
                'last_used_at' => $t['last_used_at'],
            ], $activeTokens);
        } catch (\Throwable $e) {
            $payload['tokens'] = [];
        }

        // Include audit history (last 1000 events related to this user)
        try {
            $auditLogs = $this->auditLogRepo->getBySubject('User', $id, 1000);
            $payload['audit_history'] = array_map(fn($log) => [
                'id' => $log->getId(),
                'event' => $log->getEvent(),
                'description' => $log->getDescription(),
                'created_at' => $log->getCreatedAt()->format(DATE_ATOM),
                'ip_address' => method_exists($log, 'getIpAddress') ? $log->getIpAddress() : null,
                'user_agent' => method_exists($log, 'getUserAgent') ? $log->getUserAgent() : null,
                'properties' => $log->getProperties(),
            ], $auditLogs);
        } catch (\Throwable $e) {
            $payload['audit_history'] = [];
        }

        // Include causer audit logs (actions performed BY this user)
        try {
            $causerLogs = $this->auditLogRepo->getByCauser('User', $id, 1000);
            $payload['actions_performed'] = array_map(fn($log) => [
                'id' => $log->getId(),
                'event' => $log->getEvent(),
                'description' => $log->getDescription(),
                'created_at' => $log->getCreatedAt()->format(DATE_ATOM),
                'subject_type' => $log->getSubjectType(),
                'subject_id' => $log->getSubjectId(),
            ], $causerLogs);
        } catch (\Throwable $e) {
            $payload['actions_performed'] = [];
        }

        return new JsonResponse($payload);
    }

    #[Route('/users/{id}', name: 'iam_delete_user', methods: ['DELETE'])]
    #[RequiresPermission('user', 'delete')]
    public function deleteUser(string $id): JsonResponse
    {
        // Get user email before deletion for audit
        try {
            $user = $this->userRepo->findById($id);
            $email = $user->getEmail();
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        try {
            $this->userRepo->delete($id);
            
            // Audit: User deleted (GDPR right to erasure)
            $this->auditService->logDataDeletion($id, $email);
            
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        return new JsonResponse(['ok' => true]);
    }

    // ------------------ Roles & Permissions ------------------

    #[Route('/roles', name: 'iam_roles_list', methods: ['GET'])]
    #[RequiresPermission('role', 'read')]
    public function listRoles(Request $request): JsonResponse
    {
        $tenantId = $request->query->get('tenant_id');
        $roles = $this->roleRepo->getAll($tenantId);
        $result = array_map(fn($r) => [
            'id' => $r->getId(),
            'name' => $r->getName(),
            'tenant_id' => $r->getTenantId(),
            'description' => $r->getDescription(),
            'system_role' => $r->isSystemRole(),
        ], $roles);

        return new JsonResponse($result);
    }

    #[Route('/roles', name: 'iam_roles_create', methods: ['POST'])]
    #[RequiresPermission('role', 'create')]
    public function createRole(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        
        if (empty($data['name'])) {
            return new JsonResponse(['error' => 'name required'], 400);
        }

        try {
            $role = $this->roleRepo->create($data);
            
            // Audit: Role created
            $this->auditService->logRoleCreated($role->getId(), $role->getName());
            
            return new JsonResponse(['id' => $role->getId(), 'name' => $role->getName()], 201);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/roles/{id}', name: 'iam_roles_update', methods: ['PUT','PATCH'])]
    #[RequiresPermission('role', 'update')]
    public function updateRole(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        try {
            $role = $this->roleRepo->update($id, $data);
            
            // Audit: Role updated
            $this->auditService->logRoleUpdated($role->getId(), $role->getName(), $data);
            
            return new JsonResponse(['id' => $role->getId(), 'name' => $role->getName()]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/roles/{id}', name: 'iam_roles_delete', methods: ['DELETE'])]
    #[RequiresPermission('role', 'delete')]
    public function deleteRole(string $id): JsonResponse
    {
        try {
            // Get role name before deletion for audit
            $role = $this->roleRepo->findById($id);
            $roleName = $role?->getName() ?? 'unknown';
            
            $this->roleRepo->delete($id);
            
            // Audit: Role deleted
            $this->auditService->logRoleDeleted($id, $roleName);
            
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/roles/{id}/permissions', name: 'iam_roles_list_perm', methods: ['GET'])]
    #[RequiresPermission('role', 'read')]
    public function listRolePermissions(string $id): JsonResponse
    {
        try {
            $perms = $this->roleRepo->getRolePermissions($id);
            $result = array_map(fn($p) => [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'resource' => $p->getResource(),
                'action' => $p->getAction(),
            ], $perms);
            return new JsonResponse($result);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/roles/{id}/permissions', name: 'iam_roles_assign_perm', methods: ['POST'])]
    #[RequiresPermission('role', 'update')]
    public function assignRolePermission(string $id, Request $request): JsonResponse
    {
        $permId = json_decode($request->getContent() ?: '{}', true)['permission_id'] ?? null;
        if (!$permId) {
            return new JsonResponse(['error' => 'permission_id required'], 400);
        }

        try {
            $this->roleRepo->assignPermission($id, $permId);
            
            // Get names for audit
            $role = $this->roleRepo->findById($id);
            $perm = $this->permissionRepo->findById($permId);
            
            // Audit: Permission assigned to role
            $this->auditService->logPermissionAssignedToRole(
                $id, 
                $role?->getName() ?? 'unknown',
                $permId,
                $perm?->getName() ?? 'unknown'
            );
            
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/roles/{id}/permissions/{permId}', name: 'iam_roles_revoke_perm', methods: ['DELETE'])]
    #[RequiresPermission('role', 'update')]
    public function revokeRolePermission(string $id, string $permId): JsonResponse
    {
        try {
            // Get names for audit before revoking
            $role = $this->roleRepo->findById($id);
            $perm = $this->permissionRepo->findById($permId);
            
            $this->roleRepo->revokePermission($id, $permId);
            
            // Audit: Permission revoked from role
            $this->auditService->logPermissionRevokedFromRole(
                $id,
                $role?->getName() ?? 'unknown',
                $permId,
                $perm?->getName() ?? 'unknown'
            );
            
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // ------------------ Permissions ------------------

    #[Route('/permissions', name: 'iam_permissions_list', methods: ['GET'])]
    #[RequiresPermission('permission', 'read')]
    public function listPermissions(Request $request): JsonResponse
    {
        $resource = $request->query->get('resource');
        
        $all = $resource 
            ? $this->permissionRepo->findByResource($resource)
            : $this->permissionRepo->getAll();
            
        $result = array_map(fn($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'resource' => $p->getResource(),
            'action' => $p->getAction(),
            'description' => $p->getDescription(),
        ], $all);
        
        return new JsonResponse($result);
    }

    #[Route('/permissions', name: 'iam_permissions_create', methods: ['POST'])]
    #[RequiresPermission('permission', 'create')]
    public function createPermission(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        
        if (empty($data['name']) || empty($data['resource']) || empty($data['action'])) {
            return new JsonResponse(['error' => 'name, resource, and action required'], 400);
        }

        try {
            $p = $this->permissionRepo->create($data);
            
            // Audit: Permission created
            $this->auditService->logPermissionCreated($p->getId(), $p->getName());
            
            return new JsonResponse(['id' => $p->getId(), 'name' => $p->getName()], 201);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/permissions/{id}', name: 'iam_permissions_update', methods: ['PUT','PATCH'])]
    #[RequiresPermission('permission', 'update')]
    public function updatePermission(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        try {
            $p = $this->permissionRepo->update($id, $data);
            return new JsonResponse(['id' => $p->getId(), 'name' => $p->getName()]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/permissions/{id}', name: 'iam_permissions_delete', methods: ['DELETE'])]
    #[RequiresPermission('permission', 'delete')]
    public function deletePermission(string $id): JsonResponse
    {
        try {
            // Get permission name before deletion for audit
            $perm = $this->permissionRepo->findById($id);
            $permName = $perm?->getName() ?? 'unknown';
            
            $this->permissionRepo->delete($id);
            
            // Audit: Permission deleted
            $this->auditService->logPermissionDeleted($id, $permName);
            
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // ------------------ Sessions ------------------

    #[Route('/sessions/{userId}', name: 'iam_sessions_list', methods: ['GET'])]
    #[RequiresPermission('session', 'read')]
    public function sessions(string $userId): JsonResponse
    {
        $sessions = $this->sessionManager->getActiveSessions($userId);
        return new JsonResponse($sessions);
    }

    #[Route('/sessions/revoke', name: 'iam_sessions_revoke', methods: ['POST'])]
    #[RequiresPermission('session', 'delete')]
    public function revokeSession(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        
        if (!empty($data['token'])) {
            $this->sessionManager->revokeSession($data['token']);
            
            // Audit: Session revoked
            $this->auditService->logSessionRevoked($data['token']);
            
            return new JsonResponse(['ok' => true]);
        }

        if (!empty($data['user_id']) && !empty($data['all']) && $data['all']) {
            $this->sessionManager->revokeAllSessions($data['user_id']);
            
            // Audit: All sessions revoked
            $this->auditService->logAllSessionsRevoked($data['user_id']);
            
            return new JsonResponse(['ok' => true]);
        }

        if (!empty($data['user_id']) && !empty($data['except']) && is_string($data['except'])) {
            $this->sessionManager->revokeOtherSessions($data['user_id'], $data['except']);
            return new JsonResponse(['ok' => true]);
        }

        return new JsonResponse(['error' => 'invalid payload'], 400);
    }

    #[Route('/sessions/create', name: 'iam_sessions_create', methods: ['POST'])]
    public function createSession(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        
        if (empty($data['user_id'])) {
            return new JsonResponse(['error' => 'user_id required'], 400);
        }

        try {
            $session = $this->sessionManager->createSession($data['user_id'], $data['metadata'] ?? []);
            
            // Audit: Session created
            $this->auditService->logSessionCreated($data['user_id'], $session->token);
            
            return new JsonResponse([
                'token' => $session->token,
                'expires_at' => $session->expiresAt->format('c'),
            ], 201);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/sessions/refresh', name: 'iam_sessions_refresh', methods: ['POST'])]
    public function refreshSession(Request $request): JsonResponse
    {
        $token = json_decode($request->getContent() ?: '{}', true)['token'] ?? null;
        
        if (!$token) {
            return new JsonResponse(['error' => 'token required'], 400);
        }

        try {
            $session = $this->sessionManager->refreshSession($token);
            return new JsonResponse([
                'token' => $session->token,
                'expires_at' => $session->expiresAt->format('c'),
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // ------------------ API tokens ------------------

    #[Route('/tokens/generate', name: 'iam_tokens_generate', methods: ['POST'])]
    #[RequiresPermission('token', 'create')]
    public function generateToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        if (empty($data['user_id']) || empty($data['name'])) {
            return new JsonResponse(['error' => 'user_id and name required'], 400);
        }

        $scopes = $data['scopes'] ?? [];
        $expires = isset($data['expires_at']) ? new \DateTimeImmutable($data['expires_at']) : null;

        try {
            $token = $this->tokenManager->generateToken($data['user_id'], $data['name'], $scopes, $expires);
            
            // Audit: Token generated
            $this->auditService->logTokenGenerated($data['user_id'], $token->id, $data['name']);
            
            return new JsonResponse([
                'token' => $token->token,
                'id' => $token->id,
                'expires_at' => $token->expiresAt?->format('c'),
            ], 201);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/tokens/{userId}', name: 'iam_tokens_list', methods: ['GET'])]
    #[RequiresPermission('token', 'read')]
    public function listUserTokens(string $userId): JsonResponse
    {
        $tokens = $this->tokenManager->getUserTokens($userId);
        return new JsonResponse($tokens);
    }

    #[Route('/tokens/{tokenId}', name: 'iam_tokens_revoke', methods: ['DELETE'])]
    #[RequiresPermission('token', 'delete')]
    public function revokeToken(string $tokenId): JsonResponse
    {
        try {
            $this->tokenManager->revokeToken($tokenId);
            
            // Audit: Token revoked
            $this->auditService->logTokenRevoked($tokenId);
            
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/tokens/validate', name: 'iam_tokens_validate', methods: ['POST'])]
    public function validateToken(Request $request): JsonResponse
    {
        $token = json_decode($request->getContent() ?: '{}', true)['token'] ?? null;
        
        if (!$token) {
            return new JsonResponse(['error' => 'token required'], 400);
        }

        if ($this->tokenManager->isValid($token)) {
            $scopes = $this->tokenManager->getTokenScopes($token);
            return new JsonResponse(['valid' => true, 'scopes' => $scopes]);
        }

        return new JsonResponse(['valid' => false], 401);
    }

    // ------------------ MFA ------------------

    #[Route('/mfa/{userId}/devices', name: 'iam_mfa_devices', methods: ['GET'])]
    #[RequiresPermission('mfa', 'read')]
    public function listTrustedDevices(string $userId): JsonResponse
    {
        $devices = $this->mfaVerifier->getTrustedDevices($userId);
        return new JsonResponse($devices);
    }

    #[Route('/mfa/{userId}/trust', name: 'iam_mfa_trust', methods: ['POST'])]
    public function trustDevice(string $userId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $fingerprint = $data['fingerprint'] ?? null;
        $ttl = (int) ($data['ttl_days'] ?? 30);

        if (!$fingerprint) {
            return new JsonResponse(['error' => 'fingerprint required'], 400);
        }

        $id = $this->mfaVerifier->trustDevice($userId, $fingerprint, $ttl);
        
        // Audit: Trusted device added
        $this->auditService->logTrustedDeviceAdded($userId, $id);
        
        return new JsonResponse(['trusted_device_id' => $id]);
    }

    #[Route('/mfa/{userId}/verify', name: 'iam_mfa_verify', methods: ['POST'])]
    public function verifyMfa(string $userId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $code = $data['code'] ?? null;
        $backup = $data['backup_code'] ?? null;

        if ($code) {
            $ok = $this->mfaVerifier->verifyTotp($userId, $code);
            
            // Audit: MFA verification result
            if ($ok) {
                $this->auditService->logMfaVerified($userId, 'totp');
            } else {
                $this->auditService->logMfaFailed($userId, 'totp');
            }
            
            return new JsonResponse(['ok' => $ok]);
        }

        if ($backup) {
            $ok = $this->mfaVerifier->verifyBackupCode($userId, $backup);
            
            // Audit: MFA verification result
            if ($ok) {
                $this->auditService->logMfaVerified($userId, 'backup_code');
            } else {
                $this->auditService->logMfaFailed($userId, 'backup_code');
            }
            
            return new JsonResponse(['ok' => $ok]);
        }

        return new JsonResponse(['error' => 'code or backup_code required'], 400);
    }

    #[Route('/mfa/{userId}/status', name: 'iam_mfa_status', methods: ['GET'])]
    public function mfaStatus(string $userId): JsonResponse
    {
        $required = $this->mfaVerifier->requiresMfa($userId);
        return new JsonResponse(['mfa_required' => $required]);
    }

    #[Route('/mfa/{userId}/devices/{deviceId}', name: 'iam_mfa_revoke_device', methods: ['DELETE'])]
    #[RequiresPermission('mfa', 'delete')]
    public function revokeTrustedDevice(string $userId, string $deviceId): JsonResponse
    {
        try {
            $this->mfaVerifier->revokeTrustedDevice($userId, $deviceId);
            
            // Audit: Trusted device revoked
            $this->auditService->logTrustedDeviceRevoked($userId, $deviceId);
            
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // ------------------ Password validation helper ------------------

    #[Route('/password/validate', name: 'iam_password_validate', methods: ['POST'])]
    public function validatePassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $password = $data['password'] ?? '';
        $errors = $this->passwordValidator->validate($password);
        return new JsonResponse(['errors' => $errors]);
    }
}
