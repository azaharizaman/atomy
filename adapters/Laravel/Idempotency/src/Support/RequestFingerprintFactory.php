<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Support;

use Illuminate\Http\Request;
use JsonException;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;

final readonly class RequestFingerprintFactory
{
    public function make(Request $request): RequestFingerprint
    {
        $route = $request->route();
        $path = $route !== null ? '/'.$route->uri() : '/'.$request->path();
        $method = strtoupper($request->method());
        $body = $this->canonicalBody($request);
        $raw = $method . "\n" . $path . "\n" . $body;

        return new RequestFingerprint(hash('sha256', $raw));
    }

    private function canonicalBody(Request $request): string
    {
        $content = $request->getContent();
        if ($content === '') {
            return '';
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                return 'scalar:' . hash('sha256', $content);
            }
            $normalized = $this->sortKeysRecursive($decoded);

            return json_encode($normalized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            return 'raw:' . hash('sha256', $content);
        }
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function sortKeysRecursive(array $data): array
    {
        ksort($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortKeysRecursive($value);
            }
        }

        return $data;
    }
}
