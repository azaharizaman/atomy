<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\ValueObjects;

use Nexus\Tax\Enums\TaxLevel;
use Nexus\Tax\ValueObjects\TaxJurisdiction;
use PHPUnit\Framework\TestCase;

final class TaxJurisdictionTest extends TestCase
{
    public function test_it_can_be_created_with_valid_data(): void
    {
        $jurisdiction = new TaxJurisdiction(
            code: 'US-CA',
            name: 'California',
            level: TaxLevel::State,
            countryCode: 'US',
            stateCode: 'CA',
        );

        $this->assertSame('US-CA', $jurisdiction->code);
        $this->assertSame('California', $jurisdiction->name);
        $this->assertSame(TaxLevel::State, $jurisdiction->level);
        $this->assertSame('US', $jurisdiction->countryCode);
        $this->assertSame('CA', $jurisdiction->stateCode);
    }

    public function test_it_validates_empty_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Jurisdiction code cannot be empty');

        new TaxJurisdiction(
            code: '',
            name: 'California',
            level: TaxLevel::State,
            countryCode: 'US',
        );
    }

    public function test_it_validates_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Jurisdiction name cannot be empty');

        new TaxJurisdiction(
            code: 'US-CA',
            name: '',
            level: TaxLevel::State,
            countryCode: 'US',
        );
    }

    public function test_it_validates_country_code_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Country code must be 2-letter ISO format');

        new TaxJurisdiction(
            code: 'US-CA',
            name: 'California',
            level: TaxLevel::State,
            countryCode: 'USA', // Invalid - should be 'US'
        );
    }

    public function test_it_builds_hierarchy_path(): void
    {
        $federal = new TaxJurisdiction(
            code: 'US',
            name: 'United States',
            level: TaxLevel::Federal,
            countryCode: 'US',
        );

        $state = new TaxJurisdiction(
            code: 'US-CA',
            name: 'California',
            level: TaxLevel::State,
            countryCode: 'US',
            stateCode: 'CA',
            parent: $federal,
        );

        $city = new TaxJurisdiction(
            code: 'US-CA-SF',
            name: 'San Francisco',
            level: TaxLevel::Municipal,
            countryCode: 'US',
            stateCode: 'CA',
            cityCode: 'SF',
            parent: $state,
        );

        $this->assertSame('United States → California → San Francisco', $city->getHierarchyPath());
    }

    public function test_it_checks_jurisdiction_containment(): void
    {
        $federal = new TaxJurisdiction(
            code: 'US',
            name: 'United States',
            level: TaxLevel::Federal,
            countryCode: 'US',
        );

        $state = new TaxJurisdiction(
            code: 'US-CA',
            name: 'California',
            level: TaxLevel::State,
            countryCode: 'US',
            stateCode: 'CA',
            parent: $federal,
        );

        $city = new TaxJurisdiction(
            code: 'US-CA-SF',
            name: 'San Francisco',
            level: TaxLevel::Municipal,
            countryCode: 'US',
            stateCode: 'CA',
            cityCode: 'SF',
            parent: $state,
        );

        // City is within state
        $this->assertTrue($city->isWithin($state));
        
        // City is within federal
        $this->assertTrue($city->isWithin($federal));
        
        // State is NOT within city
        $this->assertFalse($state->isWithin($city));
    }

    public function test_it_converts_to_array(): void
    {
        $jurisdiction = new TaxJurisdiction(
            code: 'US-CA-SF',
            name: 'San Francisco',
            level: TaxLevel::Municipal,
            countryCode: 'US',
            stateCode: 'CA',
            cityCode: 'SF',
            metadata: ['population' => 873965],
        );

        $array = $jurisdiction->toArray();

        $this->assertSame('US-CA-SF', $array['code']);
        $this->assertSame('San Francisco', $array['name']);
        $this->assertSame('municipal', $array['level']);
        $this->assertSame('US', $array['country_code']);
        $this->assertSame('CA', $array['state_code']);
        $this->assertSame('SF', $array['city_code']);
        $this->assertSame(['population' => 873965], $array['metadata']);
    }
}
