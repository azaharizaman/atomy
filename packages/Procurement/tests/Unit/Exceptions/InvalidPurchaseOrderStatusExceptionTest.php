<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\InvalidPurchaseOrderStatusException;
use PHPUnit\Framework\TestCase;

final class InvalidPurchaseOrderStatusExceptionTest extends TestCase
{
    public function test_for_release_returns_correct_message(): void
    {
        $id = 'po-123';
        $status = 'closed';
        $e = InvalidPurchaseOrderStatusException::forRelease($id, $status);
        
        self::assertSame("Purchase order '{$id}' cannot be released because it is in '{$status}' status.", $e->getMessage());
    }
}
