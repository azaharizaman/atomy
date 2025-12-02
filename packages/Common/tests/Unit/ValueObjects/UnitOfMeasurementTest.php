<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Common\ValueObjects\UnitOfMeasurement;
use Nexus\Common\Exceptions\InvalidValueException;

final class UnitOfMeasurementTest extends TestCase
{
    public function test_creates_valid_unit(): void
    {
        $unit = new UnitOfMeasurement('kg');
        
        $this->assertSame('kg', $unit->getValue());
        $this->assertSame('mass', $unit->getCategory());
        $this->assertSame('Kilogram', $unit->getLabel());
    }

    public function test_validates_invalid_symbol(): void
    {
        $this->expectException(InvalidValueException::class);
        
        new UnitOfMeasurement('INVALID_UNIT');
    }

    public function test_same_units_are_equal(): void
    {
        $unit1 = new UnitOfMeasurement('kg');
        $unit2 = new UnitOfMeasurement('kg');
        
        $this->assertSame($unit1->getValue(), $unit2->getValue());
        $this->assertSame($unit1->getCategory(), $unit2->getCategory());
    }

    public function test_different_units_are_not_equal(): void
    {
        $unit1 = new UnitOfMeasurement('kg');
        $unit2 = new UnitOfMeasurement('g');
        
        $this->assertNotSame($unit1->getValue(), $unit2->getValue());
    }

    public function test_to_array(): void
    {
        $unit = new UnitOfMeasurement('kg');
        $array = $unit->toArray();
        
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertSame('kg', $array['value']);
        $this->assertSame('Kilogram', $array['label']);
        $this->assertSame('mass', $array['category']);
    }

    public function test_from_array(): void
    {
        $data = ['value' => 'kg'];
        $unit = UnitOfMeasurement::fromArray($data);
        
        $this->assertSame('kg', $unit->getValue());
    }

    public function test_to_string(): void
    {
        $unit = new UnitOfMeasurement('kg');
        
        $this->assertSame('kg', $unit->toString());
    }

    public function test_can_convert_to_same_category(): void
    {
        $kg = new UnitOfMeasurement('kg');
        $g = new UnitOfMeasurement('g');
        
        $this->assertTrue($kg->canConvertTo($g));
    }

    public function test_cannot_convert_to_different_category(): void
    {
        $kg = new UnitOfMeasurement('kg');
        $m = new UnitOfMeasurement('m');
        
        $this->assertFalse($kg->canConvertTo($m));
    }
    
    public function test_is_valid(): void
    {
        $this->assertTrue(UnitOfMeasurement::isValid('kg'));
        $this->assertTrue(UnitOfMeasurement::isValid('g'));
        $this->assertFalse(UnitOfMeasurement::isValid('INVALID'));
    }
    
    public function test_values_returns_all_units(): void
    {
        $values = UnitOfMeasurement::values();
        
        $this->assertIsArray($values);
        $this->assertContains('kg', $values);
        $this->assertContains('g', $values);
    }
}
