<?php

declare(strict_types=1);

namespace Nexus\Common\Exceptions;

/**
 * Exception thrown when operations are attempted on money with different currencies.
 */
final class CurrencyMismatchException extends \LogicException
{
}
