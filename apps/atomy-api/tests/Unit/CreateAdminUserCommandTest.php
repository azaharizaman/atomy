<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Command\CreateAdminUserCommand;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

final class CreateAdminUserCommandTest extends TestCase
{
    public function testCommandCreatesUserWithHashedPassword(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $hasher->expects(self::once())->method('hash')->with('secret123')->willReturn('hashedpw');

        $repo->expects(self::once())->method('create')->with(self::callback(function ($data) {
            return isset($data['email']) && isset($data['password_hash']) && $data['password_hash'] === 'hashedpw' && in_array('ROLE_ADMIN', $data['roles'], true);
        }))->willReturn(new class implements \Nexus\Identity\Contracts\UserInterface {
            public function getId(): string { return 'id-123'; }
            public function getEmail(): string { return 'admin@example.com'; }
            public function getPasswordHash(): string { return 'hashedpw'; }
            public function getStatus(): string { return 'active'; }
            public function getName(): ?string { return 'Admin'; }
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

        $command = new CreateAdminUserCommand($repo, $hasher);

        $app = new Application();
        $app->add($command);

        $cmd = $app->find('app:create-admin');
        $tester = new CommandTester($cmd);

        // Provide input (email, name, password)
        $tester->setInputs(['admin@example.com', 'Admin', 'secret123']);

        $tester->execute([]);

        $this->assertStringContainsString('Created admin user admin@example.com', $tester->getDisplay());
    }
}
