<?php

declare(strict_types=1);

namespace App\Http\Idempotency;

use Illuminate\Http\Response;
use JsonException;
use Nexus\Laravel\Idempotency\Contracts\ReplayResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class IdempotencyReplayResponseFactory implements ReplayResponseFactoryInterface
{
    public function fromPayloadString(string $payload): SymfonyResponse
    {
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new \InvalidArgumentException('Invalid idempotency replay payload', 0, $e);
        }

        if (($data['version'] ?? null) !== 1) {
            throw new \InvalidArgumentException('Unsupported idempotency replay envelope version');
        }

        $status = (int) ($data['status'] ?? 200);
        $body = (string) ($data['body'] ?? '');
        /** @var array<string, string|array<int, string>> $headers */
        $headers = is_array($data['headers'] ?? null) ? $data['headers'] : ['Content-Type' => 'application/json'];

        return new Response($body, $status, $this->normalizeHeaders($headers));
    }

    /**
     * @param array<string, string|array<int, string>> $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                $value = $value[0] ?? '';
            }
            $out[(string) $name] = (string) $value;
        }

        return $out;
    }
}
