<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use PHPUnit\Framework\TestCase;

final class DocumentExtractionRequestTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    public function testItRecognizesForwardSlashWindowsAbsolutePaths(): void
    {
        $request = $this->makeRequest();

        $isAbsolutePath = \Closure::bind(
            fn (string $path): bool => $this->isAbsolutePath($path),
            $request,
            DocumentExtractionRequest::class,
        );

        self::assertTrue($isAbsolutePath('C:/quotes/input.pdf'));
        self::assertTrue($isAbsolutePath('C:\\quotes\\input.pdf'));
    }

    public function testItRejectsSiblingPrefixPathsOutsideAllowedBases(): void
    {
        $request = $this->makeRequest();

        $pathWithinAllowedBases = \Closure::bind(
            fn (string $path): bool => $this->pathWithinAllowedBases($path),
            $request,
            DocumentExtractionRequest::class,
        );

        self::assertFalse($pathWithinAllowedBases('/tmp-storage-evil/quote.pdf'));
        self::assertTrue($pathWithinAllowedBases('/tmp/quote.pdf'));
    }

    private function makeRequest(): DocumentExtractionRequest
    {
        $file = tempnam(sys_get_temp_dir(), 'quote-doc-');
        self::assertIsString($file);
        file_put_contents($file, 'fixture');
        $this->tempFiles[] = $file;

        return new DocumentExtractionRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            quoteSubmissionId: 'quote-1',
            filename: 'quote.pdf',
            mimeType: 'application/pdf',
            absolutePath: $file,
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }
}
