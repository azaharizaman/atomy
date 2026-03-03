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
            
            // Handle fragment preservation: insert query before '#'
            $fragment = '';
            if (($hashPos = strpos($url, '#')) !== false) {
                $fragment = substr($url, $hashPos);
                $url = substr($url, 0, $hashPos);
            }

            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . $query . $fragment;
        }

        $curl = curl_init($url);
        if ($curl === false) {
            throw new \RuntimeException('Unable to initialize cURL request.');
        }

        $headerLines = [];
        foreach ($headers as $key => $value) {
            // Validate header name (RFC 7230 token)
            if (!preg_match("/^[!#$%&'*+\-.^_`|~0-9A-Za-z]+$/", (string) $key)) {
                curl_close($curl);
                throw new \InvalidArgumentException("Invalid HTTP header name: {$key}");
            }

            // Prevent CRLF injection in values
            if (str_contains((string) $value, "\r") || str_contains((string) $value, "\n")) {
                curl_close($curl);
                throw new \InvalidArgumentException("Invalid HTTP header value for '{$key}': contains newlines.");
            }

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

            try {
                $options[CURLOPT_POSTFIELDS] = str_contains($contentType, 'application/x-www-form-urlencoded')
                    ? http_build_query($payload)
                    : json_encode($payload, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                curl_close($curl);
                throw new \RuntimeException('Failed to encode request payload: ' . $e->getMessage(), 0, $e);
            }
        }

        if (curl_setopt_array($curl, $options) === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new \RuntimeException('Failed to set cURL options: ' . $error);
        }

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
