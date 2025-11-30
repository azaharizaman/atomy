<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when a reservation has expired.
 */
class ReservationExpiredException extends Exception
{
    public static function expired(string $reservationId): self
    {
        return new self("Reservation '{$reservationId}' has expired and been released");
    }

    public static function numberNotReserved(string $number): self
    {
        return new self("Number '{$number}' is not reserved or reservation has expired");
    }
}
