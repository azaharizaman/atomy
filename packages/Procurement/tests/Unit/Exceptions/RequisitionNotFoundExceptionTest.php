<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\RequisitionNotFoundException;
use PHPUnit\Framework\TestCase;

final class RequisitionNotFoundExceptionTest extends TestCase
{
    public function test_for_id_returns_correct_message(): void
    {
        $id = 'req-123';
        $e = RequisitionNotFoundException::forId($id);
        
        self::assertSame("Requisition with ID '{$id}' not found.", $e->getMessage());
    }

    public function test_for_number_returns_correct_message(): void
    {
        $tenantId = 'tenant-1';
        $number = 'REQ-001';
        $e = RequisitionNotFoundException::forNumber($tenantId, $number);
        
        self::assertSame("Requisition '{$number}' not found for tenant '{$tenantId}'.", $e->getMessage());
    }
}
