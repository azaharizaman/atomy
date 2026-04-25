<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

use InvalidArgumentException;
use App\Adapters\Ai\Support\SamplePath;

/**
 * Immutable request envelope for provider-backed quotation document extraction.
 *
 * `tenantId`, `rfqId`, and `quoteSubmissionId` must be non-empty identifiers.
 * `mimeType` must be one of the supported provider-upload document types.
 * `absolutePath` must be an absolute path rooted under an allowed local workspace/storage base path.
 */
final readonly class DocumentExtractionRequest
{
    /**
     * @param string $tenantId Tenant identifier for scoped provider telemetry.
     * @param string $rfqId RFQ identifier associated with the quotation document.
     * @param string $quoteSubmissionId Quote submission identifier for persistence callbacks.
     * @param string $filename Original stored filename sent to the provider.
     * @param string $mimeType Supported quotation MIME type such as `application/pdf` or `image/png`.
     * @param string $absolutePath Absolute local filesystem path to the stored quotation document.
     */
    public function __construct(
        public string $tenantId,
        public string $rfqId,
        public string $quoteSubmissionId,
        public string $filename,
        public string $mimeType,
        public string $absolutePath,
    ) {
        foreach ([
            'tenantId' => $this->tenantId,
            'rfqId' => $this->rfqId,
            'quoteSubmissionId' => $this->quoteSubmissionId,
            'filename' => $this->filename,
            'mimeType' => $this->mimeType,
            'absolutePath' => $this->absolutePath,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException(sprintf('%s must not be empty.', $field));
            }
        }

        if (!in_array($this->mimeType, self::supportedMimeTypes(), true)) {
            throw new InvalidArgumentException(sprintf('Unsupported document MIME type [%s].', $this->mimeType));
        }

        if (!$this->isAbsolutePath($this->absolutePath)) {
            throw new InvalidArgumentException('absolutePath must be an absolute filesystem path.');
        }

        $resolvedPath = realpath($this->absolutePath);
        if ($resolvedPath === false) {
            throw new InvalidArgumentException('absolutePath must resolve to an existing filesystem path.');
        }

        if (!$this->pathWithinAllowedBases($resolvedPath)) {
            throw new InvalidArgumentException('absolutePath must resolve inside an allowed local storage path.');
        }
    }

    /**
     * @return list<string>
     */
    public static function supportedMimeTypes(): array
    {
        return [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    private function pathWithinAllowedBases(string $resolvedPath): bool
    {
        foreach ($this->allowedBasePaths() as $basePath) {
            $normalizedBasePath = rtrim($basePath, DIRECTORY_SEPARATOR);
            $normalizedResolvedPath = rtrim($resolvedPath, DIRECTORY_SEPARATOR);
            if (
                $normalizedBasePath !== ''
                && (
                    $normalizedResolvedPath === $normalizedBasePath
                    || str_starts_with($normalizedResolvedPath, $normalizedBasePath . DIRECTORY_SEPARATOR)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function allowedBasePaths(): array
    {
        $candidatePaths = [
            realpath(sys_get_temp_dir()) ?: '',
            $this->resolveLaravelPath('storage_path', 'app'),
            $this->resolveLaravelPath('storage_path', 'app/private'),
            $this->resolveSamplePath(),
        ];

        return array_values(array_unique(array_filter($candidatePaths, static fn (string $path): bool => $path !== '')));
    }

    private function resolveLaravelPath(string $helper, string $suffix): string
    {
        if (!function_exists($helper) || !function_exists('app')) {
            return '';
        }

        try {
            $app = app();
        } catch (\Throwable) {
            return '';
        }

        if (!is_object($app)) {
            return '';
        }

        $method = $helper === 'storage_path' ? 'storagePath' : 'basePath';
        if (!method_exists($app, $method)) {
            return '';
        }

        $resolved = $helper($suffix);

        return is_string($resolved) ? (realpath($resolved) ?: '') : '';
    }

    private function resolveSamplePath(): string
    {
        return SamplePath::root();
    }
}
