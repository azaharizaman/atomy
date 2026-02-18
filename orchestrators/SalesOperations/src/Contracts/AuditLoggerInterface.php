<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface AuditLoggerInterface
{
    public function log(string $logName, string $description, array $context = []): void;
}

interface NotificationInterface
{
    public function notify(string $recipient, string $subject, string $message, array $data = []): void;
}
