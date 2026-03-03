<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\HttpClientInterface;
use Nexus\PaymentGateway\Contracts\HttpResponseInterface;

final class CurlHttpClient implements HttpClientInterface
{
    public function __construct(
        private int $connectTimeout = 10,
        private int $requestTimeout = 30,
    ) {}

    public function request(string $method, string $url, array $payload = [], array $headers = []): HttpResponseInterface
    {
        $normalizedMethod = strtoupper($method);

        if ($normalizedMethod === 'GET' && $payload !== []) {
            $query = http_build_query($payload);
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . $query;
        }

        $curl = curl_init($url);
        if ($curl === false) {
            throw new \RuntimeException('Unable to initialize cURL request.');
        }

        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = $key . ': ' . $value;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $normalizedMethod,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->requestTimeout,
        ];

        if ($normalizedMethod !== 'GET' && $payload !== []) {
            $contentType = '';
            foreach ($headers as $headerName => $headerValue) {
                if (strtolower($headerName) === 'content-type') {
                    $contentType = strtolower($headerValue);
                    break;
                }
            }

            $options[CURLOPT_POSTFIELDS] = str_contains($contentType, 'application/x-www-form-urlencoded')
                ? http_build_query($payload)
                : json_encode($payload, JSON_THROW_ON_ERROR);
        }

        curl_setopt_array($curl, $options);

        $body = curl_exec($curl);
        if ($body === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new \RuntimeException('cURL request failed: ' . $error);
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return new CurlHttpResponse($statusCode, $body);
    }
}
