<?php

declare(strict_types=1);

namespace App\Dtos;

final readonly class JwtPayloadDto
{
    /**
     * @param array<string> $roles
     */
    public function __construct(
        public string $sub,
        public string $tenant_id,
        public string $type,
        public int $exp,
        public int $iat,
        public string $iss,
        public array $roles = [],
    ) {}

    /**
     * @param object $payload
     */
    public static function fromObject(object $payload): self
    {
        return new self(
            sub: $payload->sub,
            tenant_id: $payload->tenant_id,
            type: $payload->type,
            exp: $payload->exp,
            iat: $payload->iat,
            iss: $payload->iss,
            roles: property_exists($payload, 'roles') ? (array) $payload->roles : [],
        );
    }
}
