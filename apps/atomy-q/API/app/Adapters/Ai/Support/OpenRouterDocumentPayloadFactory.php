<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Support;

use InvalidArgumentException;
use App\Adapters\Ai\DTOs\DocumentExtractionRequest;
use App\Adapters\Ai\Contracts\DocumentPayloadFactoryInterface;

final readonly class OpenRouterDocumentPayloadFactory implements DocumentPayloadFactoryInterface
{
    public function __construct(
        private string $modelId,
        private string $parserPlugin,
        private string $pdfEngine,
        private int $maxFileSizeBytes = 10_485_760,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(DocumentExtractionRequest $request): array
    {
        if (!is_file($request->absolutePath) || !is_readable($request->absolutePath)) {
            throw new InvalidArgumentException('Quote document file is not readable.');
        }

        $fileSize = filesize($request->absolutePath);
        if (!is_int($fileSize) || $fileSize < 1) {
            throw new InvalidArgumentException('Quote document file size could not be determined.');
        }

        if ($fileSize > $this->maxFileSizeBytes) {
            throw new InvalidArgumentException(sprintf(
                'Quote document file exceeds the maximum supported size of %d bytes.',
                $this->maxFileSizeBytes,
            ));
        }

        $contents = file_get_contents($request->absolutePath);
        if ($contents === false || $contents === '') {
            throw new InvalidArgumentException('Quote document file is empty or unreadable.');
        }

        return [
            'model' => $this->modelId,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Extract this supplier quotation into concise JSON with vendor_name, quote_number, currency, total_amount, line_items (description, quantity, unit_price, total), payment_terms, delivery_terms, validity, and notes. Return JSON only.',
                    ],
                    [
                        'type' => 'file',
                        'file' => [
                            'filename' => $request->filename,
                            'file_data' => 'data:' . $request->mimeType . ';base64,' . base64_encode($contents),
                        ],
                    ],
                ],
            ]],
            'plugins' => [[
                'id' => $this->parserPlugin,
                'pdf' => [
                    'engine' => $this->pdfEngine,
                ],
            ]],
            'stream' => false,
        ];
    }
}
