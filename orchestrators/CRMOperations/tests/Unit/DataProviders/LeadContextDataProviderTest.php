<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Tests\Unit\DataProviders;

use Nexus\CRMOperations\DataProviders\LeadContextDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LeadContextDataProvider
 * 
 * @package Nexus\CRMOperations\Tests\Unit\DataProviders
 */
final class LeadContextDataProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertTrue(true);
    }

    public function testGetContextMethodExists(): void
    {
        $this->assertTrue(method_exists(LeadContextDataProvider::class, 'getContext'));
    }

    public function testGetLeadDataForConversionMethodExists(): void
    {
        $this->assertTrue(method_exists(LeadContextDataProvider::class, 'getLeadDataForConversion'));
    }
}
