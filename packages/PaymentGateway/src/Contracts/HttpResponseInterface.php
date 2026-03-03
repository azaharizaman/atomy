<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

interface HttpResponseInterface
{
    public function getStatusCode(): int;

    public function getBody(): string;
}
