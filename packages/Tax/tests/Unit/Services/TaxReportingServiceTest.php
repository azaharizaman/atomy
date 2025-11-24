<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Services;

use Nexus\Tax\Services\TaxReportingService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TaxReportingServiceTest extends TestCase
{
    private TaxReportingService $service;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new TaxReportingService($this->logger);
    }

    public function test_generate_report_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('generateReport() must be implemented by application layer');

        $this->service->generateReport(
            jurisdictionCode: 'US-CA',
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-03-31'),
            reportType: 'sales_tax_return'
        );
    }

    public function test_get_total_tax_collected_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('getTotalTaxCollected() must be implemented by application layer');

        $this->service->getTotalTaxCollected(
            jurisdictionCode: 'US-CA',
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-03-31')
        );
    }

    public function test_get_tax_by_type_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('getTaxByType() must be implemented by application layer');

        $this->service->getTaxByType(
            jurisdictionCode: 'US-CA',
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-03-31')
        );
    }

    public function test_it_logs_report_generation_attempt(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Generating tax report', $this->callback(function ($context) {
                return $context['jurisdiction'] === 'US-CA'
                    && $context['period_start'] === '2024-01-01'
                    && $context['period_end'] === '2024-03-31';
            }));

        try {
            $this->service->generateReport(
                jurisdictionCode: 'US-CA',
                periodStart: new \DateTimeImmutable('2024-01-01'),
                periodEnd: new \DateTimeImmutable('2024-03-31')
            );
        } catch (\BadMethodCallException) {
            // Expected
        }
    }
}
