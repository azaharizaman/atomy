<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\\Repository\\MfaEnrollmentRepository')]
#[ORM\Table(name: 'mfa_enrollments')]
final class MfaEnrollment
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $method;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private ?string $secret = null;

    #[ORM\Column(type: 'boolean')]
    private bool $verified = false;

    #[ORM\Column(type: 'boolean', name: 'is_primary')]
    private bool $primary = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $id, string $userId, string $method, ?string $secret = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->method = $method;
        $this->secret = $secret;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getMethod(): string { return $this->method; }
    public function getSecret(): ?string { return $this->secret; }
    public function isVerified(): bool { return $this->verified; }
    public function isPrimary(): bool { return $this->primary; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function verify(): void { $this->verified = true; }
    public function setPrimary(bool $v): void { $this->primary = $v; }
}
