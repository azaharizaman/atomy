<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\QuoteComparisonRequestDto;
use PHPUnit\Framework\TestCase;

final class QuoteComparisonRequestDtoTest extends TestCase
{
    public function testFromPayloadBuildsDto(): void
    {
        $dto = QuoteComparisonRequestDto::fromPayload([
            'rfq_id' => 'RFQ-1',
            'vendors' => [
                [
                    'vendor_id' => 'v1',
                    'lines' => [],
                ],
            ],
        ], 'idem-key-1');

        self::assertSame('RFQ-1', $dto->rfqId);
        self::assertSame('idem-key-1', $dto->idempotencyKey);
        self::assertSame('RFQ-1', $dto->toPayload()['rfq_id']);
    }

    public function testFromPayloadValidatesRequiredFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QuoteComparisonRequestDto::fromPayload(['vendors' => []], null);
    }
}
