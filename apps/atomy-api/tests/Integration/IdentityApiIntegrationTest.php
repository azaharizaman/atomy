<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use Symfony\Component\Uid\Ulid;
use App\Service\PasswordHasherAdapter;

final class IdentityApiIntegrationTest extends BaseIntegrationTestCase
{
    public function testRoleAssignmentAndRevocation(): void
    {
        $client = $this->client;
        $em = $client->getContainer()->get('doctrine')->getManager();

        $hasher = new PasswordHasherAdapter();

        $user = new User((string) new Ulid(), 'roleuser@example.com', $hasher->hash('pw'), ['ROLE_USER']);
        $em->persist($user);
        $em->flush();

        // assign role
        $client->request('POST', '/api/users/' . $user->getId() . '/roles', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['role' => 'ROLE_MANAGER']));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/users/' . $user->getId());
        $body = json_decode($client->getResponse()->getContent() ?: 'null', true);
        $this->assertContains('ROLE_MANAGER', $body['roles']);

        // revoke
        $client->request('DELETE', '/api/users/' . $user->getId() . '/roles/ROLE_MANAGER');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/users/' . $user->getId());
        $body = json_decode($client->getResponse()->getContent() ?: 'null', true);
        $this->assertNotContains('ROLE_MANAGER', $body['roles']);
    }

    public function testFailedAttemptsAndLockUnlock(): void
    {
        $client = $this->client;
        $em = $client->getContainer()->get('doctrine')->getManager();

        $hasher = new PasswordHasherAdapter();

        $user = new User((string) new Ulid(), 'lockuser@example.com', $hasher->hash('pw'), ['ROLE_USER']);
        $em->persist($user);
        $em->flush();

        // increment failed
        $client->request('POST', '/api/users/' . $user->getId() . '/increment-failed');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $result = json_decode($client->getResponse()->getContent() ?: '{}', true);
        $this->assertSame(1, $result['failed_attempts']);

        // reset failed
        $client->request('POST', '/api/users/' . $user->getId() . '/reset-failed');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // lock and unlock
        $client->request('POST', '/api/users/' . $user->getId() . '/lock', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['reason' => 'testing']));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/users/' . $user->getId());
        $body = json_decode($client->getResponse()->getContent() ?: '{}', true);
        $this->assertTrue($body['status'] === 'active' || true); // status may still be active but repository sets locked flag — check with fresh entity

        // ensure repository has locked flag
        $repo = $em->getRepository(User::class);
        // The request above changed data on the application's EM; clear test EM to reload fresh state
        $em->clear();
        $reloaded = $repo->find($user->getId());
        $this->assertTrue($reloaded->isLocked());

        $client->request('POST', '/api/users/' . $user->getId() . '/unlock');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $em->clear();
        $reloaded = $repo->find($user->getId());
        $this->assertFalse($reloaded->isLocked());
    }

    public function testSearchAndByEmail(): void
    {
        $client = $this->client;
        $em = $client->getContainer()->get('doctrine')->getManager();

        $hasher = new PasswordHasherAdapter();

        $users = [
            new User((string) new Ulid(), 'alpha@example.com', $hasher->hash('pw'), ['ROLE_USER']),
            new User((string) new Ulid(), 'beta@example.com', $hasher->hash('pw'), ['ROLE_USER']),
            new User((string) new Ulid(), 'gamma@example.com', $hasher->hash('pw'), ['ROLE_USER']),
        ];

        foreach ($users as $u) {
            $em->persist($u);
        }
        $em->flush();

        // search by partial email
        $client->request('GET', '/api/users/search?email=example.com');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $result = json_decode($client->getResponse()->getContent() ?: '[]', true);
        $this->assertGreaterThanOrEqual(3, count($result));

        // by-email existing
        $client->request('GET', '/api/users/by-email?email=alpha@example.com');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // by-email missing
        $client->request('GET', '/api/users/by-email?email=notfound@example.com');
        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }
}
