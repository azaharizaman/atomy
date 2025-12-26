<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\WebhookStatus;

interface WebhookReceiptStorageInterface
{
    public function store(string $eventId, WebhookStatus $status, ?string $message = null): void;
    public function getStatus(string $eventId): ?WebhookStatus;
}
