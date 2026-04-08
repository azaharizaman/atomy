<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Mappers;

use App\Models\MfaEnrollment as MfaEnrollmentModel;
use DateTimeImmutable;
use Nexus\Identity\Contracts\MfaEnrollmentDataInterface;
use Nexus\Identity\ValueObjects\MfaMethod;

final readonly class LaravelMfaEnrollmentMapper
{
    public static function fromModel(MfaEnrollmentModel $model): MfaEnrollmentDataInterface
    {
        return new class($model) implements MfaEnrollmentDataInterface {
            public function __construct(private MfaEnrollmentModel $model)
            {
            }

            public function getId(): string
            {
                return (string) $this->model->id;
            }

            public function getUserId(): string
            {
                return (string) $this->model->user_id;
            }

            public function getMethod(): MfaMethod
            {
                $method = strtolower(trim((string) $this->model->method));

                return MfaMethod::tryFrom($method) ?? MfaMethod::TOTP;
            }

            public function getSecret(): array
            {
                $raw = $this->model->secret;
                if (is_array($raw)) {
                    return $raw;
                }

                if (is_string($raw) && trim($raw) !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }

                    return ['secret' => $raw];
                }

                return [];
            }

            public function isActive(): bool
            {
                $verified = (bool) ($this->model->verified ?? false);
                $revoked = (bool) ($this->model->revoked ?? false);

                return $verified && ! $revoked;
            }

            public function isPrimary(): bool
            {
                return (bool) ($this->model->is_primary ?? false);
            }

            public function getCreatedAt(): DateTimeImmutable
            {
                return $this->model->created_at?->toImmutable() ?? new DateTimeImmutable();
            }

            public function getLastUsedAt(): ?DateTimeImmutable
            {
                return $this->model->last_used_at?->toImmutable();
            }

            public function getVerifiedAt(): ?DateTimeImmutable
            {
                return $this->model->verified_at?->toImmutable();
            }

            public function isVerified(): bool
            {
                return (bool) ($this->model->verified ?? false);
            }
        };
    }
}
