<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Exceptions;

use RuntimeException;

abstract class IdempotencyException extends RuntimeException
{
}
