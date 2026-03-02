<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\QuotationIntelligence\Services\QuoteNormalizationService;
use Nexus\QuotationIntelligence\Exceptions\UomNormalizationException;
use Nexus\Uom\Services\UomConversionEngine;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Currency\ValueObjects\CurrencyPair;
use Nexus\Finance\ValueObjects\ExchangeRate;
use Psr\Log\LoggerInterface;

final class QuoteNormalizationServiceTest extends TestCase
{
    private $uomEngine;
    private $rateProvider;
    private $logger;
    private $service;

    protected function setUp(): void
    {
        $this->uomEngine = $this->createMock(UomConversionEngine::class);
        $this->rateProvider = $this->createMock(ExchangeRateProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new QuoteNormalizationService(
            $this->uomEngine,
            $this->rateProvider,
            $this->logger
        );
    }

    public function test_normalizes_quantity_via_uom_engine(): void
    {
        // 1. Arrange
        $this->uomEngine->expects($this->once())
            ->method('convert')
            ->with(10.0, 'BOX-12', 'UNIT')
            ->willReturn(120.0);

        // 2. Act
        $result = $this->service->normalizeQuantity(10.0, 'BOX-12', 'UNIT');

        // 3. Assert
        $this->assertSame(120.0, $result);
    }

    public function test_normalization_throws_exception_on_engine_error(): void
    {
        // 1. Arrange
        $this->uomEngine->method('convert')
            ->willThrowException(new \InvalidArgumentException('Incompatible categories'));

        // 2. Act & Assert
        $this->expectException(UomNormalizationException::class);
        $this->service->normalizeQuantity(10.0, 'KG', 'L');
    }

    public function test_normalizes_price_via_currency_provider(): void
    {
        // 1. Arrange
        $rate = new ExchangeRate('EUR', 'USD', '1.10', new \DateTimeImmutable());

        $this->rateProvider->expects($this->once())
            ->method('getRate')
            ->with($this->callback(fn(CurrencyPair $p) => $p->getFromCode() === 'EUR' && $p->getToCode() === 'USD'))
            ->willReturn($rate);

        // 2. Act
        $result = $this->service->normalizePrice(100.0, 'EUR', 'USD');

        // 3. Assert
        $this->assertEqualsWithDelta(110.0, $result, 0.0001);
    }
}
