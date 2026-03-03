<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\HttpResponseInterface;

final readonly class CurlHttpResponse implements HttpResponseInterface
{
    public function __construct(
        private int $statusCode,
        private string $body
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
