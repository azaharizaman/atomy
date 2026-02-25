<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

interface MessageInterface
{
    public function getId(): string;
    public function getTicketId(): string;
    public function getSenderId(): string;
    public function getBody(): string;
    public function getChannel(): string; // e.g., Email, SMS
    public function getTimestamp(): \DateTimeImmutable;
}
