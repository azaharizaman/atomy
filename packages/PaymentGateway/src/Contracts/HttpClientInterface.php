<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

interface HttpClientInterface
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function request(string $method, string $url, array $payload = [], array $headers = []): HttpResponseInterface;
}
