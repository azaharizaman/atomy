<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

abstract class BaseIntegrationTestCase extends WebTestCase
{
    protected ?KernelBrowser $client = null;
    protected ?User $adminUser = null;
    protected string $adminUserId;

    protected function setUp(): void
    {
        // Make sure previous kernel is shut down before creating clients
        static::ensureKernelShutdown();

        $this->client = static::createClient();
        $kernelDir = $this->client->getKernel()->getProjectDir();
        $dbFile = $kernelDir . '/var/test.sqlite';

        if (file_exists($dbFile)) {
            @unlink($dbFile);
        }

        // Ensure container and EntityManager are available
        $em = $this->client->getContainer()->get('doctrine')->getManager();

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        if (empty($metadatas)) {
            // no metadata scanned – nothing to do
            return;
        }

        $schemaTool = new SchemaTool($em);
        try {
            $schemaTool->dropSchema($metadatas);
        } catch (\Throwable $e) {
            // ignore drop failures if nothing existed
        }

        $schemaTool->createSchema($metadatas);

        // Create admin user with full permissions for tests
        $this->createAdminUser();
    }

    /**
     * Create an admin user with all identity permissions for testing.
     */
    protected function createAdminUser(): void
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        
        $this->adminUserId = (string) new Ulid();
        
        // Create admin role with all identity permissions
        $adminRole = new Role(
            (string) new Ulid(),
            'Admin',
            'Super admin role with all permissions',
            null,  // tenantId
            true   // systemRole
        );
        $em->persist($adminRole);
        
        // Create all identity permissions
        $permissions = [
            ['user', 'create'], ['user', 'read'], ['user', 'update'], ['user', 'delete'],
            ['role', 'create'], ['role', 'read'], ['role', 'update'], ['role', 'delete'],
            ['permission', 'create'], ['permission', 'read'], ['permission', 'update'], ['permission', 'delete'],
            ['session', 'create'], ['session', 'read'], ['session', 'update'], ['session', 'delete'],
            ['token', 'create'], ['token', 'read'], ['token', 'update'], ['token', 'delete'],
            ['mfa', 'create'], ['mfa', 'read'], ['mfa', 'update'], ['mfa', 'delete'],
        ];
        
        foreach ($permissions as [$resource, $action]) {
            $permName = 'IDENTITY_' . strtoupper($resource) . '_' . strtoupper($action);
            $permission = new Permission(
                (string) new Ulid(),
                $permName,
                $resource,
                $action,
                "Permission to {$action} {$resource}"
            );
            $em->persist($permission);
            $adminRole->addPermission($permission);
        }
        
        // Create admin user with ROLE_ADMIN (super admin bypass in voter)
        $this->adminUser = new User(
            $this->adminUserId,
            'admin@test.com',
            password_hash('AdminPass123!', PASSWORD_DEFAULT),
            ['ROLE_ADMIN']
        );
        $em->persist($this->adminUser);
        $em->flush();
    }

    /**
     * Login as the admin user for authenticated requests.
     */
    protected function loginAsAdmin(): void
    {
        if ($this->adminUser === null) {
            throw new \RuntimeException('Admin user not created. Call createAdminUser() first.');
        }
        
        $this->client->loginUser($this->adminUser);
    }

    protected function tearDown(): void
    {
        // keep parent's tearDown behavior
        parent::tearDown();

        // clean up DB file between tests
        // remove DB using the existing client if present
        $kernelDir = null;
        if (isset($this->client) && $this->client instanceof KernelBrowser) {
            $kernelDir = $this->client->getKernel()->getProjectDir();
        }
        if ($kernelDir === null) {
            return;
        }
        $dbFile = $kernelDir . '/var/test.sqlite';
        if (file_exists($dbFile)) {
            @unlink($dbFile);
        }
    }
}
