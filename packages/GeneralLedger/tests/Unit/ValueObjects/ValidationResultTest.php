<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\ValueObjects\ValidationResult;

final class ValidationResultTest extends TestCase
{
    public function test_it_can_create_valid_result(): void
    {
        $result = ValidationResult::valid(['warning']);
        
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
        $this->assertCount(1, $result->warnings);
    }

    public function test_it_can_create_invalid_result(): void
    {
        $result = ValidationResult::invalid(['error'], ['warning']);
        
        $this->assertFalse($result->isValid);
        $this->assertCount(1, $result->errors);
        $this->assertCount(1, $result->warnings);
    }
}
