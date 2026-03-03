<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\DataProviders;

use Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest;

final class OnboardingContextDataProvider
{
    /**
     * @return array<string, mixed>
     */
    public function build(DataOnboardingRequest $request): array
    {
        $fileSize = @filesize($request->sourcePath);

        return [
            'tenant_id' => $request->tenantId,
            'source_path' => $request->sourcePath,
            'source_file_size_bytes' => $fileSize === false ? 0 : $fileSize,
            'cleanup_after_import' => (bool) ($request->options['cleanup'] ?? true),
            'source_sha256' => is_file($request->sourcePath) ? (string) hash_file('sha256', $request->sourcePath) : null,
        ];
    }
}
