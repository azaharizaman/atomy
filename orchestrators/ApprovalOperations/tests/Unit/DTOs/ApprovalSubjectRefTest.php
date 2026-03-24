<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Tests\Unit\DTOs;

use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\Exceptions\InvalidApprovalSubjectRefException;
use PHPUnit\Framework\TestCase;

final class ApprovalSubjectRefTest extends TestCase
{
    public function testRejectsEmptySubjectType(): void
    {
        $this->expectException(InvalidApprovalSubjectRefException::class);
        new ApprovalSubjectRef(' ', 'x');
    }

    public function testRejectsEmptySubjectId(): void
    {
        $this->expectException(InvalidApprovalSubjectRefException::class);
        new ApprovalSubjectRef('type', "\t");
    }

    public function testAcceptsValidSubjects(): void
    {
        $ref = new ApprovalSubjectRef('purchase_order', 'po-123');
        self::assertSame('purchase_order', $ref->subjectType);
        self::assertSame('po-123', $ref->subjectId);
    }
}
