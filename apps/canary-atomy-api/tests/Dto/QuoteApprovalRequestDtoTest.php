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
        QuoteApprovalRequestDto::fromPayload([
            'decision' => 'hold',
            'reason' => 'Nope',
        ]);
    }
}
