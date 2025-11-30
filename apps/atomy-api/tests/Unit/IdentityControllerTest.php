<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Controller\Api\IdentityController;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class IdentityControllerTest extends TestCase
{
    public function testCreateUser(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $hasher->expects(self::once())->method('hash')->with('pw')->willReturn('h');
        $repo->expects(self::once())->method('emailExists')->with('a@b.com')->willReturn(false);
        $repo->expects(self::once())->method('create')->with(self::callback(function ($data) {
            return $data['email'] === 'a@b.com' && $data['password_hash'] === 'h';
        }))->willReturn(new class implements \Nexus\Identity\Contracts\UserInterface {
            public function getId(): string { return '01FZQ9X4C4G7V8GQXXXXXXX'; }
            public function getEmail(): string { return 'a@b.com'; }
            public function getPasswordHash(): string { return 'h'; }
            public function getStatus(): string { return 'active'; }
            public function getName(): ?string { return null; }
            public function getCreatedAt(): \DateTimeInterface { return new \DateTimeImmutable(); }
            public function getUpdatedAt(): \DateTimeInterface { return new \DateTimeImmutable(); }
            public function getEmailVerifiedAt(): ?\DateTimeInterface { return null; }
            public function isActive(): bool { return true; }
            public function isLocked(): bool { return false; }
            public function isEmailVerified(): bool { return false; }
            public function getTenantId(): ?string { return null; }
            public function getPasswordChangedAt(): ?\DateTimeInterface { return null; }
            public function hasMfaEnabled(): bool { return false; }
            public function getMetadata(): ?array { return null; }
        });

        $controller = new IdentityController($repo, $hasher);

        $req = new Request([], [], [], [], [], [], json_encode(['email' => 'a@b.com', 'password' => 'pw']));
        $resp = $controller->create($req);

        $this->assertSame(201, $resp->getStatusCode());
        $data = json_decode($resp->getContent(), true);
        $this->assertSame('a@b.com', $data['email']);
    }

    public function testGetUserNotFound(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $repo->expects(self::once())->method('findById')->with('missing')->will($this->throwException(new \Nexus\Identity\Exceptions\UserNotFoundException('no')));

        $controller = new IdentityController($repo, $hasher);
        $resp = $controller->get('missing');
        $this->assertSame(404, $resp->getStatusCode());
    }
}
