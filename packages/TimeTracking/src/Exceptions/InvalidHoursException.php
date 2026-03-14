<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Exceptions;

/**
 * Thrown when hours are negative or exceed 24h/day/user (BUS-PRO-0056).
 */
final class InvalidHoursException extends TimeTrackingException
{
}
