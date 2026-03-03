<?php

declare(strict_types=1);

namespace Nexus\ESG\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\ESG\Services\CarbonNormalizer;
use Nexus\ESG\ValueObjects\EmissionsAmount;

final class CarbonNormalizerTest extends TestCase
{
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CarbonNormalizer();
    }

    public function test_normalizes_kg_to_tonnes(): void
    {
        $amount = new EmissionsAmount(5000, 'kg');
        $result = $this->normalizer->normalize($amount, 'tonnes');

        $this->assertEquals(5.0, $result->amount);
        $this->assertSame('tonnes', $result->unit);
    }

    public function test_aggregates_multiple_units(): void
    {
        $amounts = [
            new EmissionsAmount(1.0, 'tonnes'), // 1.0
            new EmissionsAmount(500, 'kg'),     // 0.5
            new EmissionsAmount(1000000, 'g'),  // 1.0
        ];

        $result = $this->normalizer->aggregate($amounts);

        $this->assertEquals(2.5, $result->amount);
        $this->assertSame('tonnes', $result->unit);
    }
}
