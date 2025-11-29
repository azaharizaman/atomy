<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use App\Service\PasswordHasherAdapter;
use Symfony\Component\Uid\Ulid;

/**
 * Integration tests for Identity Admin Services:
 * - Token Management
 * - Session Management
 * - Role Management
 * - Permission Management
 * - MFA Verification
 * - Password Validation
 */
final class IdentityAdminServicesTest extends BaseIntegrationTestCase
{
    private ?User $testUser = null;
    private string $testUserId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Login as admin for authenticated endpoints
        $this->loginAsAdmin();
        
        // Create a test user for all tests
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $hasher = new PasswordHasherAdapter();
        
        $this->testUserId = (string) new Ulid();
        $this->testUser = new User(
            $this->testUserId,
            'testuser@example.com',
            $hasher->hash('ValidPass123!'),
            ['ROLE_USER']
        );
        $em->persist($this->testUser);
        $em->flush();
    }

    // ==================== TOKEN MANAGEMENT TESTS ====================

    public function testGenerateToken(): void
    {
        $this->client->request(
            'POST',
            '/api/identity/tokens/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'user_id' => $this->testUserId,
                'name' => 'Test API Token',
                'scopes' => ['read', 'write'],
            ])
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $this->assertArrayHasKey('token', $body);
        $this->assertArrayHasKey('id', $body);
        $this->assertNotEmpty($body['token']);
        $this->assertNotEmpty($body['id']);
    }

    public function testGenerateTokenWithExpiration(): void
    {
        $expiresAt = (new \DateTimeImmutable('+7 days'))->format('c');
        
        $this->client->request(
            'POST',
            '/api/identity/tokens/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'user_id' => $this->testUserId,
                'name' => 'Expiring Token',
                'scopes' => ['read'],
                'expires_at' => $expiresAt,
            ])
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $this->assertArrayHasKey('expires_at', $body);
        $this->assertNotNull($body['expires_at']);
    }

    public function testListUserTokens(): void
    {
        // First generate a token
        $this->client->request(
            'POST',
            '/api/identity/tokens/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'user_id' => $this->testUserId,
                'name' => 'Token for listing',
                'scopes' => ['read'],
            ])
        );

        // Then list tokens
        $this->client->request('GET', '/api/identity/tokens/' . $this->testUserId);
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '[]', true);
        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
    }

    public function testValidateToken(): void
    {
        // Generate a token
        $this->client->request(
            'POST',
            '/api/identity/tokens/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'user_id' => $this->testUserId,
                'name' => 'Token for validation',
                'scopes' => ['read', 'write'],
            ])
        );

        $generateResponse = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $token = $generateResponse['token'];

        // Validate the token
        $this->client->request(
            'POST',
            '/api/identity/tokens/validate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $token])
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $this->assertTrue($body['valid']);
        $this->assertEquals(['read', 'write'], $body['scopes']);
    }

    public function testRevokeToken(): void
    {
        // Generate a token
        $this->client->request(
            'POST',
            '/api/identity/tokens/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'user_id' => $this->testUserId,
                'name' => 'Token to revoke',
                'scopes' => ['read'],
            ])
        );

        $generateResponse = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $tokenId = $generateResponse['id'];
        $token = $generateResponse['token'];

        // Revoke the token
        $this->client->request('DELETE', '/api/identity/tokens/' . $tokenId);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // Verify the token is no longer valid
        $this->client->request(
            'POST',
            '/api/identity/tokens/validate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $token])
        );

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testGenerateTokenRequiresUserId(): void
    {
        $this->client->request(
            'POST',
            '/api/identity/tokens/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Missing user_id'])
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    // ==================== SESSION MANAGEMENT TESTS ====================

    public function testCreateSession(): void
    {
        $this->client->request(
            'POST',
            '/api/identity/sessions/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'user_id' => $this->testUserId,
                'metadata' => ['ip' => '127.0.0.1', 'user_agent' => 'Test Browser'],
            ])
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $this->assertArrayHasKey('token', $body);
        $this->assertArrayHasKey('expires_at', $body);
    }

    public function testListUserSessions(): void
    {
        // Create a session
        $this->client->request(
            'POST',
            '/api/identity/sessions/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['user_id' => $this->testUserId])
        );

        // List sessions
        $this->client->request('GET', '/api/identity/sessions/' . $this->testUserId);
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '[]', true);
        $this->assertIsArray($body);
    }

    public function testRefreshSession(): void
    {
        // Create a session
        $this->client->request(
            'POST',
            '/api/identity/sessions/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['user_id' => $this->testUserId])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $token = $createResponse['token'];

        // Refresh the session
        $this->client->request(
            'POST',
            '/api/identity/sessions/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $token])
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $this->assertArrayHasKey('token', $body);
        $this->assertArrayHasKey('expires_at', $body);
    }

    public function testRevokeSession(): void
    {
        // Create a session
        $this->client->request(
            'POST',
            '/api/identity/sessions/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['user_id' => $this->testUserId])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $token = $createResponse['token'];

        // Revoke the session
        $this->client->request(
            'POST',
            '/api/identity/sessions/revoke',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $token])
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testRevokeAllSessions(): void
    {
        // Create multiple sessions
        for ($i = 0; $i < 3; $i++) {
            $this->client->request(
                'POST',
                '/api/identity/sessions/create',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['user_id' => $this->testUserId])
            );
        }

        // Revoke all sessions
        $this->client->request(
            'POST',
            '/api/identity/sessions/revoke',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['user_id' => $this->testUserId, 'all' => true])
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    // ==================== ROLE MANAGEMENT TESTS ====================

    public function testCreateRole(): void
    {
        $this->client->request(
            'POST',
            '/api/identity/roles',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'ROLE_TEST_MANAGER',
                'description' => 'Test manager role',
            ])
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $this->assertArrayHasKey('id', $body);
        $this->assertEquals('ROLE_TEST_MANAGER', $body['name']);
    }

    public function testListRoles(): void
    {
        // Create a role first
        $this->client->request(
            'POST',
            '/api/identity/roles',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'ROLE_FOR_LIST'])
        );

        // List roles
        $this->client->request('GET', '/api/identity/roles');
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '[]', true);
        $this->assertIsArray($body);
    }

    public function testUpdateRole(): void
    {
        // Create a role
        $this->client->request(
            'POST',
            '/api/identity/roles',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'ROLE_TO_UPDATE'])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $roleId = $createResponse['id'];

        // Update the role
        $this->client->request(
            'PUT',
            '/api/identity/roles/' . $roleId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['description' => 'Updated description'])
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteRole(): void
    {
        // Create a role
        $this->client->request(
            'POST',
            '/api/identity/roles',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'ROLE_TO_DELETE'])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $roleId = $createResponse['id'];

        // Delete the role
        $this->client->request('DELETE', '/api/identity/roles/' . $roleId);
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    // ==================== PERMISSION MANAGEMENT TESTS ====================

    public function testCreatePermission(): void
    {
        $this->client->request(
            'POST',
            '/api/identity/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'invoice.create',
                'resource' => 'invoice',
                'action' => 'create',
                'description' => 'Create invoices',
            ])
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $this->assertArrayHasKey('id', $body);
        $this->assertEquals('invoice.create', $body['name']);
    }

    public function testListPermissions(): void
    {
        // Create a permission first
        $this->client->request(
            'POST',
            '/api/identity/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'order.view',
                'resource' => 'order',
                'action' => 'view',
            ])
        );

        // List permissions
        $this->client->request('GET', '/api/identity/permissions');
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '[]', true);
        $this->assertIsArray($body);
    }

    public function testListPermissionsByResource(): void
    {
        // Create permissions with different resources
        $this->client->request(
            'POST',
            '/api/identity/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'product.view', 'resource' => 'product', 'action' => 'view'])
        );

        $this->client->request(
            'POST',
            '/api/identity/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'product.edit', 'resource' => 'product', 'action' => 'edit'])
        );

        // Filter by resource
        $this->client->request('GET', '/api/identity/permissions?resource=product');
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '[]', true);
        $this->assertIsArray($body);
        // All returned permissions should have resource = 'product'
        foreach ($body as $p) {
            $this->assertEquals('product', $p['resource']);
        }
    }

    public function testAssignPermissionToRole(): void
    {
        // Create a role
        $this->client->request(
            'POST',
            '/api/identity/roles',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'ROLE_WITH_PERM'])
        );
        $roleId = json_decode($this->client->getResponse()->getContent() ?: '{}', true)['id'];

        // Create a permission
        $this->client->request(
            'POST',
            '/api/identity/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'customer.delete', 'resource' => 'customer', 'action' => 'delete'])
        );
        $permId = json_decode($this->client->getResponse()->getContent() ?: '{}', true)['id'];

        // Assign permission to role
        $this->client->request(
            'POST',
            '/api/identity/roles/' . $roleId . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['permission_id' => $permId])
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // Verify the permission was assigned
        $this->client->request('GET', '/api/identity/roles/' . $roleId . '/permissions');
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '[]', true);
        $this->assertNotEmpty($body);
        $this->assertEquals($permId, $body[0]['id']);
    }

    public function testRevokePermissionFromRole(): void
    {
        // Create a role
        $this->client->request(
            'POST',
            '/api/identity/roles',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'ROLE_REVOKE_PERM'])
        );
        $roleId = json_decode($this->client->getResponse()->getContent() ?: '{}', true)['id'];

        // Create a permission
        $this->client->request(
            'POST',
            '/api/identity/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'vendor.delete', 'resource' => 'vendor', 'action' => 'delete'])
        );
        $permId = json_decode($this->client->getResponse()->getContent() ?: '{}', true)['id'];

        // Assign permission to role
        $this->client->request(
            'POST',
            '/api/identity/roles/' . $roleId . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['permission_id' => $permId])
        );

        // Revoke permission from role
        $this->client->request('DELETE', '/api/identity/roles/' . $roleId . '/permissions/' . $permId);
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // Verify the permission was revoked
        $this->client->request('GET', '/api/identity/roles/' . $roleId . '/permissions');
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '[]', true);
        $this->assertEmpty($body);
    }

    // ==================== PASSWORD VALIDATION TESTS ====================

    public function testRegisterWithWeakPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/identity/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'weakpass@example.com',
                'password' => '123',  // Too weak
            ])
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $this->assertArrayHasKey('errors', $body);
        $this->assertNotEmpty($body['errors']);
    }

    public function testRegisterWithStrongPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/identity/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'strongpass@example.com',
                'password' => 'StrongP@ssw0rd123!',
            ])
        );

        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
    }

    public function testChangePasswordWithWeakPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/identity/users/' . $this->testUserId . '/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['password' => 'weak'])
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $this->assertArrayHasKey('errors', $body);
    }

    // ==================== MFA TESTS ====================

    public function testListTrustedDevices(): void
    {
        $this->client->request('GET', '/api/identity/mfa/' . $this->testUserId . '/devices');
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '[]', true);
        $this->assertIsArray($body);
    }

    public function testTrustDevice(): void
    {
        $this->client->request(
            'POST',
            '/api/identity/mfa/' . $this->testUserId . '/trust',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fingerprint' => 'device-fingerprint-abc123',
                'name' => 'Test Device',
            ])
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testRevokeTrustedDevice(): void
    {
        // First trust a device
        $fingerprint = 'device-to-revoke-' . uniqid();
        $this->client->request(
            'POST',
            '/api/identity/mfa/' . $this->testUserId . '/trust',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fingerprint' => $fingerprint,
                'name' => 'Device to revoke',
            ])
        );
        
        $trustResponse = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        $deviceId = $trustResponse['trusted_device_id'];

        // Revoke it using the device ID
        $this->client->request(
            'DELETE',
            '/api/identity/mfa/' . $this->testUserId . '/devices/' . $deviceId
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    // ==================== USER EXPORT (GDPR) TEST ====================

    public function testExportUser(): void
    {
        $this->client->request('GET', '/api/identity/users/' . $this->testUserId . '/export');
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        
        // Core user data
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('email', $body);
        $this->assertArrayHasKey('created_at', $body);
        $this->assertEquals($this->testUserId, $body['id']);
        $this->assertEquals('testuser@example.com', $body['email']);
        
        // GDPR-enhanced data: sessions, tokens, audit history
        $this->assertArrayHasKey('sessions', $body);
        $this->assertIsArray($body['sessions']);
        
        $this->assertArrayHasKey('tokens', $body);
        $this->assertIsArray($body['tokens']);
        
        $this->assertArrayHasKey('audit_history', $body);
        $this->assertIsArray($body['audit_history']);
        
        $this->assertArrayHasKey('actions_performed', $body);
        $this->assertIsArray($body['actions_performed']);
    }

    public function testExportUserWithSessionsAndTokens(): void
    {
        // First create a session and token for the user
        $this->client->request(
            'POST',
            '/api/identity/sessions/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'user_id' => $this->testUserId,
            ])
        );
        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        
        $this->client->request(
            'POST',
            '/api/identity/tokens/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'user_id' => $this->testUserId,
                'name' => 'Export Test Token',
                'scopes' => ['read'],
            ])
        );
        $this->assertSame(201, $this->client->getResponse()->getStatusCode());
        
        // Now export and verify the data is included
        $this->client->request('GET', '/api/identity/users/' . $this->testUserId . '/export');
        
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        
        $body = json_decode($this->client->getResponse()->getContent() ?: '{}', true);
        
        // Should have at least one session
        $this->assertNotEmpty($body['sessions'], 'User should have at least one session');
        $this->assertArrayHasKey('id', $body['sessions'][0]);
        $this->assertArrayHasKey('created_at', $body['sessions'][0]);
        
        // Should have at least one token
        $this->assertNotEmpty($body['tokens'], 'User should have at least one token');
        $this->assertArrayHasKey('id', $body['tokens'][0]);
        $this->assertArrayHasKey('name', $body['tokens'][0]);
        $this->assertArrayHasKey('scopes', $body['tokens'][0]);
        
        // Audit history and actions_performed arrays should exist 
        // (may be empty if no direct user-subject logs exist)
        $this->assertArrayHasKey('audit_history', $body);
        $this->assertIsArray($body['audit_history']);
        $this->assertArrayHasKey('actions_performed', $body);
        $this->assertIsArray($body['actions_performed']);
    }

    public function testExportUserNotFound(): void
    {
        $this->client->request('GET', '/api/identity/users/nonexistent-id/export');
        
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }
}
