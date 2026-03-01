<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\Loyalty\ValueObjects\PointBucket;

class PointBucketTest extends TestCase
{
    public function test_bucket_is_expired(): void
    {
        $now = new DateTimeImmutable();
        $past = $now->modify('-1 day');
        $future = $now->modify('+1 day');

        $bucket1 = new PointBucket('b1', 100, 100, $now, $past);
        $bucket2 = new PointBucket('b2', 100, 100, $now, $future);
        $bucket3 = new PointBucket('b3', 100, 100, $now, null);

        $this->assertTrue($bucket1->isExpired($now));
        $this->assertFalse($bucket2->isExpired($now));
        $this->assertFalse($bucket3->isExpired($now));
    }
}
