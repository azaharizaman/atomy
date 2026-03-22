<?php

declare(strict_types=1);

namespace App\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;

/**
 * Appends idempotency-related machine-readable `code` values to the OpenAPI info description.
 */
final class IdempotencyErrorCodesDocumentTransformer implements DocumentTransformer
{
    private const APPEND = <<<'MD'


### Idempotency error `code` values

JSON error bodies may include a string `code` for clients and tooling. Idempotency-related values:

| `code` | HTTP | Meaning |
|--------|------|---------|
| `idempotency_key_required` | 400 | `Idempotency-Key` header missing or empty |
| `idempotency_key_invalid` | 400 | Key failed validation (e.g. length) |
| `idempotency_fingerprint_conflict` | 409 | Same key, different request body |
| `idempotency_in_progress` | 409 | Same key; prior attempt not finished |
| `idempotency_operation_ref_missing` | 500 | Route has no name (misconfiguration) |
| `idempotency_tenant_missing` | 500 | Tenant not on request after auth |
| `idempotency_replay_missing` | 500 | Replay outcome without stored payload |
| `idempotency_record_missing` | 500 | Internal idempotency state inconsistency |
| `idempotency_envelope_too_large` | 500 | Response too large to store for replay |

MD;

    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $document->info->setDescription($document->info->description.self::APPEND);
    }
}
