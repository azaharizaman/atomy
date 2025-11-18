<?php

declare(strict_types=1);

namespace Nexus\Notifier\Exceptions;

/**
 * Invalid Recipient Exception
 *
 * Thrown when recipient data is invalid or incomplete.
 */
final class InvalidRecipientException extends NotificationException
{
    public static function missingContactInfo(string $recipientId, string $channel): self
    {
        return new self("Recipient '{$recipientId}' has no contact information for channel '{$channel}'.");
    }

    public static function invalidEmail(string $email): self
    {
        return new self("Invalid email address: '{$email}'.");
    }

    public static function invalidPhone(string $phone): self
    {
        return new self("Invalid phone number: '{$phone}'.");
    }
}
