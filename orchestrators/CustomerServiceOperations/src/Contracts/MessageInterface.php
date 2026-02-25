<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

use Nexus\CustomerServiceOperations\Enums\MessageChannel;

interface MessageInterface
{
    public function getId(): string;
    public function getTicketId(): string;
    public function getSenderId(): string;
    public function getBody(): string;
    public function getChannel(): MessageChannel;
    public function getTimestamp(): \DateTimeImmutable;
}
