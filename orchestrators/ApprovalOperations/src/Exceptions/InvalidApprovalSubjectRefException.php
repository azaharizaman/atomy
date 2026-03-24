<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Exceptions;

final class InvalidApprovalSubjectRefException extends \DomainException
{
    public static function emptySubjectType(): self
    {
        return new self('subjectType cannot be empty.');
    }

    public static function emptySubjectId(): self
    {
        return new self('subjectId cannot be empty.');
    }
}
