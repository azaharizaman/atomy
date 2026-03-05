<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\QuoteApprovalRequestDto;
use PHPUnit\Framework\TestCase;

final class QuoteApprovalRequestDtoTest extends TestCase
{
    public function testFromPayloadBuildsDto(): void
    {
        $dto = QuoteApprovalRequestDto::fromPayload([
            'decision' => 'Approve',
            'reason' => 'Reviewed',
        ]);

        self::assertSame('approve', $dto->decision);
        self::assertSame('Reviewed', $dto->reason);
    }

    public function testFromPayloadRejectsInvalidDecision(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('decision must be approve or reject.');
        QuoteApprovalRequestDto::fromPayload([
            'decision' => 'hold',
            'reason' => 'Nope',
        ]);
    }

    public function testFromPayloadRejectsInvalidReason(): void
    {
        // Case 1: Empty string
        try {
            QuoteApprovalRequestDto::fromPayload([
                'decision' => 'approve',
                'reason' => '  ',
            ]);
            $this->fail('Expected InvalidArgumentException for empty reason.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('reason is required.', $e->getMessage());
        }

        // Case 2: Non-string (null)
        try {
            QuoteApprovalRequestDto::fromPayload([
                'decision' => 'approve',
                'reason' => null,
            ]);
            $this->fail('Expected InvalidArgumentException for null reason.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('reason must be a string.', $e->getMessage());
        }

        // Case 3: Non-string (array)
        try {
            QuoteApprovalRequestDto::fromPayload([
                'decision' => 'approve',
                'reason' => ['some' => 'array'],
            ]);
            $this->fail('Expected InvalidArgumentException for array reason.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('reason must be a string.', $e->getMessage());
        }
    }
}
