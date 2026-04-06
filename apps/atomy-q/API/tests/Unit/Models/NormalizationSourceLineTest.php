<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\NormalizationSourceLine;
use DomainException;
use PHPUnit\Framework\TestCase;

final class NormalizationSourceLineTest extends TestCase
{
    public function test_get_rfq_line_item_id_throws_when_value_is_null(): void
    {
        $model = new NormalizationSourceLine();
        $model->forceFill([
            'rfq_line_item_id' => null,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('rfq_line_item_id is null');

        $model->getRfqLineItemId();
    }
}
