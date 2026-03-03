<?php

declare(strict_types=1);

namespace Nexus\Audit\Enums;

enum HashAlgorithm: string
{
    case SHA256 = 'sha256';
    case SHA384 = 'sha384';
    case SHA512 = 'sha512';
    case BLAKE2B = 'blake2b';
}
