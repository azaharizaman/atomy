<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\Exceptions\AiTransportInvalidResponseException;
use App\Adapters\Ai\Support\OpenRouterDocumentExtractionMapper;
use PHPUnit\Framework\TestCase;

final class OpenRouterDocumentExtractionMapperTest extends TestCase
{
    public function testItMapsOpenRouterJsonContentToExtractedLines(): void
    {
        $mapper = new OpenRouterDocumentExtractionMapper();

        $result = $mapper->map([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'vendor_name' => 'Kuching Utama Sdn Bhd',
                        'quote_number' => '1809/2975',
                        'currency' => 'RM',
                        'total_amount' => 84.8,
                        'line_items' => [[
                            'description' => 'BREAKDOWN ASSIST JUMP START VEHICLE',
                            'quantity' => 1,
                            'unit_price' => 80,
                            'total' => 84.8,
                        ]],
                        'payment_terms' => '50% deposit required before work can be carried out',
                        'validity' => '30 days',
                    ], JSON_THROW_ON_ERROR),
                ],
            ]],
        ]);

        self::assertSame('Kuching Utama Sdn Bhd', $result['vendor_name']);
        self::assertSame('1809/2975', $result['quote_number']);
        self::assertSame('MYR', $result['currency']);
        self::assertSame(84.8, $result['total_amount']);
        self::assertSame('BREAKDOWN ASSIST JUMP START VEHICLE', $result['lines'][0]['description']);
        self::assertSame(1.0, $result['lines'][0]['quantity']);
        self::assertSame(80.0, $result['lines'][0]['unit_price']);
        self::assertSame(84.8, $result['lines'][0]['total']);
        self::assertSame('50% deposit required before work can be carried out', $result['payment_terms']);
        self::assertSame('30 days', $result['validity']);
    }

    public function testItMapsEmptyLineItemsAndMissingOptionalFields(): void
    {
        $mapper = new OpenRouterDocumentExtractionMapper();

        $result = $mapper->map([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'vendor_name' => 'Kuching Utama Sdn Bhd',
                        'quote_number' => '1809/2975',
                        'currency' => 'RM',
                        'total_amount' => 84.8,
                        'line_items' => [],
                    ], JSON_THROW_ON_ERROR),
                ],
            ]],
        ]);

        self::assertSame([], $result['lines']);
        self::assertNull($result['payment_terms']);
        self::assertNull($result['validity']);
    }

    public function testItThrowsWhenResponseContentIsNotJson(): void
    {
        $mapper = new OpenRouterDocumentExtractionMapper();

        $this->expectException(AiTransportInvalidResponseException::class);
        $this->expectExceptionMessage('Document provider response content was not valid JSON.');

        $mapper->map([
            'choices' => [[
                'message' => [
                    'content' => 'not-json',
                ],
            ]],
        ]);
    }

    public function testItThrowsWhenChoicesAreMissing(): void
    {
        $mapper = new OpenRouterDocumentExtractionMapper();

        $this->expectException(AiTransportInvalidResponseException::class);
        $this->expectExceptionMessage('Document provider response did not include message content.');

        $mapper->map([]);
    }

    public function testItStripsAnyCaseInsensitiveMarkdownFenceAndParsesFormattedNumbers(): void
    {
        $mapper = new OpenRouterDocumentExtractionMapper();

        $result = $mapper->map([
            'choices' => [[
                'message' => [
                    'content' => <<<JSON
```JSON
{"currency":"RM","total_amount":"(1,234.56)","line_items":[{"description":"Pump","quantity":"1,234.56","unit_price":"(45.67)","total":"1,188.89"}]}
```
JSON,
                ],
            ]],
        ]);

        self::assertSame('MYR', $result['currency']);
        self::assertSame(-1234.56, $result['total_amount']);
        self::assertSame(1234.56, $result['lines'][0]['quantity']);
        self::assertSame(-45.67, $result['lines'][0]['unit_price']);
        self::assertSame(1188.89, $result['lines'][0]['total']);
    }

    public function testItRejectsAmbiguousLocaleFormattedNumbers(): void
    {
        $mapper = new OpenRouterDocumentExtractionMapper();

        $result = $mapper->map([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'currency' => 'RM',
                        'total_amount' => '1.234,56',
                        'line_items' => [[
                            'description' => 'Pump',
                            'quantity' => '1.234,56',
                            'unit_price' => '12-3',
                            'total' => '1.234,56',
                        ]],
                    ], JSON_THROW_ON_ERROR),
                ],
            ]],
        ]);

        self::assertNull($result['total_amount']);
        self::assertSame(1.0, $result['lines'][0]['quantity']);
        self::assertNull($result['lines'][0]['unit_price']);
        self::assertNull($result['lines'][0]['total']);
    }
}
