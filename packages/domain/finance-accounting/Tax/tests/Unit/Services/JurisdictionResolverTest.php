<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Services;

use Nexus\Tax\Enums\ServiceClassification;
use Nexus\Tax\Enums\TaxLevel;
use Nexus\Tax\Exceptions\JurisdictionNotResolvedException;
use Nexus\Tax\Services\JurisdictionResolver;
use Nexus\Tax\ValueObjects\TaxContext;
use Nexus\Tax\ValueObjects\TaxJurisdiction;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class JurisdictionResolverTest extends TestCase
{
    private JurisdictionResolver $resolver;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resolver = new JurisdictionResolver($this->logger);
    }

    public function test_it_resolves_us_jurisdiction_from_state(): void
    {
        $address = [
            'country' => 'US',
            'state' => 'CA',
            'city' => 'San Francisco',
        ];

        $jurisdiction = $this->resolver->resolveFromAddress($address);

        $this->assertInstanceOf(TaxJurisdiction::class, $jurisdiction);
        $this->assertSame('US-CA', $jurisdiction->code);
        $this->assertSame('California', $jurisdiction->name);
        $this->assertSame(TaxLevel::State, $jurisdiction->level);
    }

    public function test_it_resolves_canadian_jurisdiction_from_province(): void
    {
        $address = [
            'country' => 'CA',
            'state' => 'BC',
            'city' => 'Vancouver',
        ];

        $jurisdiction = $this->resolver->resolveFromAddress($address);

        $this->assertInstanceOf(TaxJurisdiction::class, $jurisdiction);
        $this->assertSame('CA-BC', $jurisdiction->code);
        $this->assertSame('British Columbia', $jurisdiction->name);
        $this->assertSame(TaxLevel::State, $jurisdiction->level);
    }

    public function test_it_throws_exception_when_country_missing(): void
    {
        $this->expectException(JurisdictionNotResolvedException::class);
        $this->expectExceptionMessage('Address must include country code');

        $this->resolver->resolveFromAddress([
            'city' => 'London',
        ]);
    }

    public function test_it_resolves_domestic_transaction(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
        );

        $jurisdiction = $this->resolver->resolve($context);

        $this->assertSame('US-CA', $jurisdiction->code);
        $this->assertSame('California', $jurisdiction->name);
    }

    public function test_it_resolves_cross_border_digital_service(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-002',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'GB-VAT',
            taxType: 'vat',
            customerId: 'CUST-002',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'GB'], // Cross-border
            serviceClassification: ServiceClassification::DigitalService,
        );

        $jurisdiction = $this->resolver->resolve($context);

        // Digital services use destination (customer location)
        $this->assertSame('GB', $jurisdiction->code);
    }

    public function test_it_resolves_jurisdiction_hierarchy(): void
    {
        $address = [
            'country' => 'US',
            'state' => 'CA',
            'city' => 'San Francisco',
        ];

        $hierarchy = $this->resolver->resolveHierarchy($address);

        $this->assertIsArray($hierarchy);
        $this->assertGreaterThanOrEqual(1, count($hierarchy));
        
        // First should be highest level (federal/country)
        $this->assertSame(TaxLevel::Federal, $hierarchy[0]->level);
        
        // Last should be lowest level (state/local)
        $lastIndex = count($hierarchy) - 1;
        $this->assertContains($hierarchy[$lastIndex]->level, [TaxLevel::State, TaxLevel::Municipal]);
    }

    public function test_it_checks_if_address_in_jurisdiction(): void
    {
        $address = [
            'country' => 'US',
            'state' => 'CA',
            'city' => 'San Francisco',
        ];

        // Should be in California jurisdiction
        $this->assertTrue($this->resolver->isInJurisdiction($address, 'US-CA'));
        
        // Should not be in Texas jurisdiction
        $this->assertFalse($this->resolver->isInJurisdiction($address, 'US-TX'));
    }

    public function test_it_logs_resolution_attempts(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-003',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-003',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
        );

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Resolving jurisdiction', $this->anything());

        $this->resolver->resolve($context);
    }
}
