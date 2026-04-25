<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use PHPUnit\Framework\TestCase;
use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use App\Adapters\Ai\Support\OpenRouterDocumentPayloadFactory;

final class OpenRouterDocumentPayloadFactoryTest extends TestCase
{
    public function testItBuildsOpenRouterPdfPayloadWithMistralOcrPlugin(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'quote-pdf-');
        self::assertIsString($file);
        file_put_contents($file, '%PDF-1.7 sample');

        $factory = new OpenRouterDocumentPayloadFactory(
            modelId: 'baidu/qianfan-ocr-fast:free',
            parserPlugin: 'file-parser',
            pdfEngine: 'mistral-ocr',
        );

        try {
            $payload = $factory->build(new DocumentExtractionRequest(
                tenantId: 'tenant-1',
                rfqId: 'rfq-1',
                quoteSubmissionId: 'quote-1',
                filename: 'quote.pdf',
                mimeType: 'application/pdf',
                absolutePath: $file,
            ));

            self::assertSame('baidu/qianfan-ocr-fast:free', $payload['model']);
            self::assertArrayHasKey('plugins', $payload);
            self::assertIsArray($payload['plugins']);
            self::assertCount(1, $payload['plugins']);
            self::assertArrayHasKey(0, $payload['plugins']);
            self::assertArrayHasKey('id', $payload['plugins'][0]);
            self::assertArrayHasKey('pdf', $payload['plugins'][0]);
            self::assertSame('file-parser', $payload['plugins'][0]['id']);
            self::assertSame('mistral-ocr', $payload['plugins'][0]['pdf']['engine']);
            self::assertArrayHasKey('messages', $payload);
            self::assertIsArray($payload['messages']);
            self::assertCount(1, $payload['messages']);
            self::assertArrayHasKey('content', $payload['messages'][0]);
            self::assertIsArray($payload['messages'][0]['content']);
            self::assertCount(2, $payload['messages'][0]['content']);
            self::assertSame('file', $payload['messages'][0]['content'][1]['type']);
            self::assertArrayHasKey('file', $payload['messages'][0]['content'][1]);
            self::assertArrayHasKey('file_data', $payload['messages'][0]['content'][1]['file']);
            self::assertStringStartsWith(
                'data:application/pdf;base64,',
                $payload['messages'][0]['content'][1]['file']['file_data'],
            );
        } finally {
            @unlink($file);
        }
    }

    public function testItRejectsFilesAboveConfiguredMaxSize(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'quote-pdf-');
        self::assertIsString($file);
        file_put_contents($file, '123456');

        $factory = new OpenRouterDocumentPayloadFactory(
            modelId: 'baidu/qianfan-ocr-fast:free',
            parserPlugin: 'file-parser',
            pdfEngine: 'mistral-ocr',
            maxFileSizeBytes: 5,
        );

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Quote document file exceeds the maximum supported size');

            $factory->build(new DocumentExtractionRequest(
                tenantId: 'tenant-1',
                rfqId: 'rfq-1',
                quoteSubmissionId: 'quote-1',
                filename: 'quote.pdf',
                mimeType: 'application/pdf',
                absolutePath: $file,
            ));
        } finally {
            @unlink($file);
        }
    }
}
