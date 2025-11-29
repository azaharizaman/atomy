<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use App\Service\PasswordHasherAdapter;
use Doctrine\ORM\Tools\SchemaTool;
use App\Tests\Integration\BaseIntegrationTestCase;
use Symfony\Component\Uid\Ulid;

final class IdentityIntegrationTest extends BaseIntegrationTestCase
{
    private $entityManager;

    // No common setUp: create a fresh schema per test using createClient()

    public function testLoginAndLockoutFlow(): void
    {
        $client = $this->client;

        $hasher = new PasswordHasherAdapter();

        // EntityManager prepared by BaseIntegrationTestCase
        $em = $client->getContainer()->get('doctrine')->getManager();

        // create a user
        $user = new User((string) new Ulid(), 'bob@example.com', $hasher->hash('pw'), ['ROLE_USER']);
        $em->persist($user);
        $em->flush();

        // successful login with correct password
        $client->request('POST', '/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'bob@example.com', 'password' => 'pw']));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // attempt failed logins until lock
        for ($i = 0; $i < 6; $i++) {
            $client->request('POST', '/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'bob@example.com', 'password' => 'wrong']));
        }

        // reload user and assert locked
        $repo = $em->getRepository(User::class);
        $u = $repo->find($user->getId());
        $this->assertTrue($u->isLocked());
    }

    public function testAdminApiCreateUser(): void
    {
        $client = $this->client;


        // EntityManager prepared by BaseIntegrationTestCase
        $em = $client->getContainer()->get('doctrine')->getManager();

        $hasher = new PasswordHasherAdapter();

        // create admin user
        $admin = new User((string) new Ulid(), 'admin@example.com', $hasher->hash('adminpw'), ['ROLE_ADMIN']);
        $em->persist($admin);
        $em->flush();

        // login as admin
        $client->request('POST', '/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'admin@example.com', 'password' => 'adminpw']));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // create new user via API
        $client->request('POST', '/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'new@example.com', 'password' => 'pw']));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
    }
}
