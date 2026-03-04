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
        $fileSize = filesize($request->sourcePath);
        $sourceSha256 = null;
        if (is_file($request->sourcePath)) {
            $hash = hash_file('sha256', $request->sourcePath);
            $sourceSha256 = is_string($hash) ? $hash : null;
        }

        return [
            'tenant_id' => $request->tenantId,
            'source_path' => $request->sourcePath,
            'source_file_size_bytes' => $fileSize === false ? null : $fileSize,
            'cleanup_after_import' => (bool) ($request->options['cleanup'] ?? true),
            'source_sha256' => $sourceSha256,
        ];
    }
}
