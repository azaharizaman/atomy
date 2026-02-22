<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\FinanceOperations\Services\CashPositionService;
use Nexus\FinanceOperations\Contracts\TreasuryDataProviderInterface;
use Nexus\FinanceOperations\DTOs\CashFlow\CashPositionRequest;
use Nexus\FinanceOperations\DTOs\CashFlow\CashPositionResult;
use Nexus\FinanceOperations\DTOs\CashFlow\CashFlowForecastRequest;
use Nexus\FinanceOperations\DTOs\CashFlow\CashFlowForecastResult;
use Nexus\FinanceOperations\Exceptions\CashFlowException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Simple test double for currency conversion.
 * Used to mock currency conversion in tests.
 */
class TestCurrencyConverter
{
    public function convert(string $amount, string $fromCurrency, string $toCurrency): string
    {
        // Simple conversion for testing - EUR to USD at 1.1 rate
        if ($fromCurrency === 'EUR' && $toCurrency === 'USD') {
            return (string)((float)$amount * 1.1);
        }
        return $amount;
    }
}

/**
 * Unit tests for CashPositionService.
 *
 * Tests cover:
 * - Single bank account position retrieval
 * - All accounts position retrieval (null account ID)
 * - Multi-currency consolidation with currency converter
 * - Multi-currency consolidation without currency converter
 * - Cash flow forecast generation
 * - Empty positions handling
 * - Error handling when data provider throws exceptions
 * - Cash position retrieval failed exception
 * - Forecast generation failed exception
 *
 * @since 1.0.0
 */
final class CashPositionServiceTest extends TestCase
{
    private MockObject|TreasuryDataProviderInterface $dataProviderMock;
    private MockObject|LoggerInterface $loggerMock;
    private TestCurrencyConverter $currencyConverter;
    private CashPositionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataProviderMock = $this->createMock(TreasuryDataProviderInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->currencyConverter = new TestCurrencyConverter();

        $this->service = new CashPositionService(
            $this->dataProviderMock,
            $this->currencyConverter,
            $this->loggerMock
        );
    }

    // =========================================================================
    // Test Suite: Service Instantiation
    // =========================================================================

    /**
     * Test that service can be instantiated with default logger.
     */
    public function testServiceCanBeInstantiatedWithDefaultLogger(): void
    {
        $service = new CashPositionService($this->dataProviderMock);
        
        $this->assertInstanceOf(CashPositionService::class, $service);
    }

    /**
     * Test that service can be instantiated with null currency converter.
     */
    public function testServiceCanBeInstantiatedWithNullCurrencyConverter(): void
    {
        $service = new CashPositionService($this->dataProviderMock, null, $this->loggerMock);
        
        $this->assertInstanceOf(CashPositionService::class, $service);
    }

    // =========================================================================
    // Test Suite: getPosition() method
    // =========================================================================

    /**
     * Scenario 1: Single bank account position retrieval.
     *
     * Tests that when a specific bank account ID is provided, the service
     * returns the cash position for that single account.
     */
    public function testGetPositionForSingleAccount(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: 'account-001',
            currency: 'USD'
        );

        $positionData = [
            'accountId' => 'account-001',
            'accountName' => 'Primary Checking',
            'balance' => '15000.00',
            'currency' => 'USD',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashPosition')
            ->with('tenant-001', 'account-001')
            ->willReturn($positionData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Calculating cash position', $this->anything());

        // Act
        $result = $this->service->getPosition($request);

        // Assert
        $this->assertInstanceOf(CashPositionResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('tenant-001', $result->tenantId);
        $this->assertCount(1, $result->positions);
        $this->assertSame('15000', $result->consolidatedBalance);
        $this->assertSame('USD', $result->currency);
    }

    /**
     * Scenario 2: All accounts position retrieval (null account ID).
     *
     * Tests that when bank account ID is null, the service retrieves
     * positions for all bank accounts.
     */
    public function testGetPositionForAllAccounts(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: null,
            currency: 'USD'
        );

        $bankAccounts = [
            ['id' => 'account-001', 'name' => 'Primary Checking'],
            ['id' => 'account-002', 'name' => 'Savings Account'],
        ];

        $positionData1 = [
            'accountId' => 'account-001',
            'accountName' => 'Primary Checking',
            'balance' => '10000.00',
            'currency' => 'USD',
        ];

        $positionData2 = [
            'accountId' => 'account-002',
            'accountName' => 'Savings Account',
            'balance' => '5000.00',
            'currency' => 'USD',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with('tenant-001')
            ->willReturn($bankAccounts);

        $this->dataProviderMock
            ->expects($this->exactly(2))
            ->method('getCashPosition')
            ->willReturnMap([
                ['tenant-001', 'account-001', $positionData1],
                ['tenant-001', 'account-002', $positionData2],
            ]);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Calculating cash position', $this->anything());

        // Act
        $result = $this->service->getPosition($request);

        // Assert
        $this->assertInstanceOf(CashPositionResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('tenant-001', $result->tenantId);
        $this->assertCount(2, $result->positions);
        $this->assertSame('15000', $result->consolidatedBalance);
    }

    /**
     * Scenario 3: Multi-currency consolidation with currency converter.
     *
     * Tests that when accounts have different currencies and a currency
     * converter is provided, balances are converted to target currency.
     */
    public function testGetPositionWithCurrencyConversion(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: null,
            currency: 'USD'
        );

        $bankAccounts = [
            ['id' => 'account-001', 'name' => 'USD Account'],
            ['id' => 'account-002', 'name' => 'EUR Account'],
        ];

        $positionData1 = [
            'accountId' => 'account-001',
            'accountName' => 'USD Account',
            'balance' => '10000.00',
            'currency' => 'USD',
        ];

        $positionData2 = [
            'accountId' => 'account-002',
            'accountName' => 'EUR Account',
            'balance' => '5000.00',
            'currency' => 'EUR',
        ];

        // Use the real TestCurrencyConverter which converts EUR to USD at 1.1 rate
        // 5000 EUR * 1.1 = 5500 USD

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with('tenant-001')
            ->willReturn($bankAccounts);

        $this->dataProviderMock
            ->expects($this->exactly(2))
            ->method('getCashPosition')
            ->willReturnMap([
                ['tenant-001', 'account-001', $positionData1],
                ['tenant-001', 'account-002', $positionData2],
            ]);

        // Act
        $result = $this->service->getPosition($request);

        // Assert
        $this->assertInstanceOf(CashPositionResult::class, $result);
        $this->assertSame('15500', $result->consolidatedBalance);
    }

    /**
     * Scenario 4: Multi-currency consolidation without currency converter.
     *
     * Tests that when accounts have different currencies but no currency
     * converter is provided, balances are summed as-is.
     */
    public function testGetPositionWithoutCurrencyConverter(): void
    {
        // Arrange - create service without currency converter
        $service = new CashPositionService($this->dataProviderMock, null, $this->loggerMock);

        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: null,
            currency: 'USD'
        );

        $bankAccounts = [
            ['id' => 'account-001', 'name' => 'USD Account'],
            ['id' => 'account-002', 'name' => 'EUR Account'],
        ];

        $positionData1 = [
            'accountId' => 'account-001',
            'accountName' => 'USD Account',
            'balance' => '10000.00',
            'currency' => 'USD',
        ];

        $positionData2 = [
            'accountId' => 'account-002',
            'accountName' => 'EUR Account',
            'balance' => '5000.00',
            'currency' => 'EUR',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with('tenant-001')
            ->willReturn($bankAccounts);

        $this->dataProviderMock
            ->expects($this->exactly(2))
            ->method('getCashPosition')
            ->willReturnMap([
                ['tenant-001', 'account-001', $positionData1],
                ['tenant-001', 'account-002', $positionData2],
            ]);

        // Act
        $result = $service->getPosition($request);

        // Assert
        $this->assertInstanceOf(CashPositionResult::class, $result);
        // Without converter, EUR balance is still added as-is (5000)
        $this->assertSame('15000', $result->consolidatedBalance);
    }

    /**
     * Scenario 5: Empty positions handling.
     *
     * Tests that when no positions are found, the service returns an
     * empty result with zero consolidated balance.
     */
    public function testGetPositionWithEmptyPositions(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: null,
            currency: 'USD'
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with('tenant-001')
            ->willReturn([]);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Calculating cash position', $this->anything());

        // Act
        $result = $this->service->getPosition($request);

        // Assert
        $this->assertInstanceOf(CashPositionResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('0', $result->consolidatedBalance);
        $this->assertCount(0, $result->positions);
    }

    /**
     * Scenario 6: Single account returns empty data.
     *
     * Tests that when a single account query returns empty data,
     * an empty result is returned.
     */
    public function testGetPositionWithSingleEmptyAccount(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: 'account-001',
            currency: 'USD'
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashPosition')
            ->with('tenant-001', 'account-001')
            ->willReturn([]);

        // Act
        $result = $this->service->getPosition($request);

        // Assert
        $this->assertInstanceOf(CashPositionResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('0', $result->consolidatedBalance);
    }

    /**
     * Scenario 7: Error handling - data provider throws exception.
     *
     * Tests that when the data provider throws an exception, a
     * CashFlowException is thrown with proper error details.
     */
    public function testGetPositionThrowsExceptionOnDataProviderError(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: 'account-001',
            currency: 'USD'
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashPosition')
            ->with('tenant-001', 'account-001')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Failed to calculate cash position', $this->anything());

        // Assert
        $this->expectException(CashFlowException::class);

        // Act
        $this->service->getPosition($request);
    }

    // =========================================================================
    // Test Suite: getConsolidatedPosition() method
    // =========================================================================

    /**
     * Scenario 1: Get consolidated position for multiple accounts.
     *
     * Tests that consolidated position returns aggregated data across
     * all bank accounts with the correct structure.
     */
    public function testGetConsolidatedPosition(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $targetCurrency = 'USD';

        $bankAccounts = [
            ['id' => 'account-001', 'name' => 'Primary Checking'],
            ['id' => 'account-002', 'name' => 'Savings Account'],
        ];

        $positionData1 = [
            'balance' => '10000.00',
            'currency' => 'USD',
        ];

        $positionData2 = [
            'balance' => '5000.00',
            'currency' => 'USD',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with($tenantId)
            ->willReturn($bankAccounts);

        $this->dataProviderMock
            ->expects($this->exactly(2))
            ->method('getCashPosition')
            ->willReturnMap([
                [$tenantId, 'account-001', $positionData1],
                [$tenantId, 'account-002', $positionData2],
            ]);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Calculating consolidated cash position', $this->anything());

        // Act
        $result = $this->service->getConsolidatedPosition($tenantId, $targetCurrency);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('positions', $result);
        $this->assertArrayHasKey('consolidated_balance', $result);
        $this->assertArrayHasKey('currency', $result);
        $this->assertArrayHasKey('account_count', $result);
        $this->assertCount(2, $result['positions']);
        $this->assertSame('15000', $result['consolidated_balance']);
        $this->assertSame('USD', $result['currency']);
        $this->assertSame(2, $result['account_count']);
    }

    /**
     * Scenario 2: Consolidated position with currency conversion.
     *
     * Tests that consolidated position properly converts currencies
     * when a currency converter is available.
     */
    public function testGetConsolidatedPositionWithCurrencyConversion(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $targetCurrency = 'USD';

        $bankAccounts = [
            ['id' => 'account-001', 'name' => 'USD Account'],
            ['id' => 'account-002', 'name' => 'EUR Account'],
        ];

        $positionData1 = [
            'balance' => '10000.00',
            'currency' => 'USD',
        ];

        $positionData2 = [
            'balance' => '5000.00',
            'currency' => 'EUR',
        ];

        // TestCurrencyConverter converts EUR to USD at 1.1 rate

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with($tenantId)
            ->willReturn($bankAccounts);

        $this->dataProviderMock
            ->expects($this->exactly(2))
            ->method('getCashPosition')
            ->willReturnMap([
                [$tenantId, 'account-001', $positionData1],
                [$tenantId, 'account-002', $positionData2],
            ]);

        // Act
        $result = $this->service->getConsolidatedPosition($tenantId, $targetCurrency);

        // Assert

        // Assert
        $this->assertSame('15500', $result['consolidated_balance']);
    }

    /**
     * Scenario 3: Consolidated position with alternate account ID key.
     *
     * Tests that the service handles bank accounts returned with
     * 'bank_account_id' key instead of 'id'.
     */
    public function testGetConsolidatedPositionWithAlternateAccountIdKey(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $targetCurrency = 'USD';

        $bankAccounts = [
            ['bank_account_id' => 'account-001', 'name' => 'Primary Checking'],
        ];

        $positionData = [
            'balance' => '10000.00',
            'currency' => 'USD',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with($tenantId)
            ->willReturn($bankAccounts);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashPosition')
            ->with($tenantId, 'account-001')
            ->willReturn($positionData);

        // Act
        $result = $this->service->getConsolidatedPosition($tenantId, $targetCurrency);

        // Assert
        $this->assertCount(1, $result['positions']);
    }

    /**
     * Scenario 4: Consolidated position skips accounts without ID.
     *
     * Tests that accounts without valid ID are skipped during
     * consolidation.
     */
    public function testGetConsolidatedPositionSkipsAccountsWithoutId(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $targetCurrency = 'USD';

        $bankAccounts = [
            ['id' => 'account-001', 'name' => 'Valid Account'],
            ['name' => 'Invalid Account - No ID'],
            ['bank_account_id' => 'account-002', 'name' => 'Another Valid'],
        ];

        $positionData1 = ['balance' => '10000.00', 'currency' => 'USD'];
        $positionData2 = ['balance' => '5000.00', 'currency' => 'USD'];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with($tenantId)
            ->willReturn($bankAccounts);

        $this->dataProviderMock
            ->expects($this->exactly(2))
            ->method('getCashPosition')
            ->willReturnMap([
                [$tenantId, 'account-001', $positionData1],
                [$tenantId, 'account-002', $positionData2],
            ]);

        // Act
        $result = $this->service->getConsolidatedPosition($tenantId, $targetCurrency);

        // Assert
        $this->assertSame(2, $result['account_count']);
    }

    /**
     * Scenario 5: Consolidated position with empty accounts.
     *
     * Tests that consolidated position returns zero balance when
     * no bank accounts exist.
     */
    public function testGetConsolidatedPositionWithEmptyAccounts(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $targetCurrency = 'USD';

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with($tenantId)
            ->willReturn([]);

        // Act
        $result = $this->service->getConsolidatedPosition($tenantId, $targetCurrency);

        // Assert
        $this->assertCount(0, $result['positions']);
        $this->assertSame('0', $result['consolidated_balance']);
        $this->assertSame(0, $result['account_count']);
    }

    /**
     * Scenario 6: Error handling - consolidated position exception.
     *
     * Tests that when data provider fails during consolidation,
     * a CashFlowException is thrown.
     */
    public function testGetConsolidatedPositionThrowsExceptionOnError(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        $targetCurrency = 'USD';

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with($tenantId)
            ->willThrowException(new \RuntimeException('Connection failed'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Failed to get consolidated position', $this->anything());

        // Assert
        $this->expectException(CashFlowException::class);

        // Act
        $this->service->getConsolidatedPosition($tenantId, $targetCurrency);
    }

    // =========================================================================
    // Test Suite: generateForecast() method
    // =========================================================================

    /**
     * Scenario 1: Cash flow forecast generation.
     *
     * Tests that the forecast is generated correctly with inflows,
     * outflows, and net cash flow calculated.
     */
    public function testGenerateForecast(): void
    {
        // Arrange
        $request = new CashFlowForecastRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            forecastDays: 30
        );

        $forecastData = [
            'inflows' => [
                ['amount' => '10000.00'],
                ['amount' => '5000.00'],
            ],
            'outflows' => [
                ['amount' => '3000.00'],
                ['amount' => '2000.00'],
            ],
            'daily_forecast' => [
                ['date' => '2024-01-01', 'inflow' => '10000.00', 'outflow' => '3000.00', 'net' => '7000.00', 'balance' => '17000.00'],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashFlowForecast')
            ->with('tenant-001', '2024-Q1')
            ->willReturn($forecastData);

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Generating cash flow forecast', $this->anything());

        // Act
        $result = $this->service->generateForecast($request);

        // Assert
        $this->assertInstanceOf(CashFlowForecastResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('tenant-001', $result->tenantId);
        $this->assertCount(2, $result->inflows);
        $this->assertCount(2, $result->outflows);
        $this->assertSame('10000', $result->netCashFlow); // 15000 - 5000
    }

    /**
     * Scenario 2: Forecast with empty inflows and outflows.
     *
     * Tests that forecast generation handles empty forecast data
     * correctly, returning zero net cash flow.
     */
    public function testGenerateForecastWithEmptyData(): void
    {
        // Arrange
        $request = new CashFlowForecastRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            forecastDays: 30
        );

        $forecastData = [
            'inflows' => [],
            'outflows' => [],
            'daily_forecast' => [],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashFlowForecast')
            ->with('tenant-001', '2024-Q1')
            ->willReturn($forecastData);

        // Act
        $result = $this->service->generateForecast($request);

        // Assert
        $this->assertInstanceOf(CashFlowForecastResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('0', $result->netCashFlow);
        $this->assertCount(0, $result->inflows);
        $this->assertCount(0, $result->outflows);
    }

    /**
     * Scenario 3: Forecast with missing keys in data.
     *
     * Tests that forecast generation handles missing keys in the
     * returned data gracefully.
     */
    public function testGenerateForecastWithMissingDataKeys(): void
    {
        // Arrange
        $request = new CashFlowForecastRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            forecastDays: 30
        );

        $forecastData = [];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashFlowForecast')
            ->with('tenant-001', '2024-Q1')
            ->willReturn($forecastData);

        // Act
        $result = $this->service->generateForecast($request);

        // Assert
        $this->assertInstanceOf(CashFlowForecastResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('0', $result->netCashFlow);
    }

    /**
     * Scenario 4: Forecast with transaction amounts as floats.
     *
     * Tests that the service correctly handles amounts that may be
     * returned as floats instead of strings.
     */
    public function testGenerateForecastWithFloatAmounts(): void
    {
        // Arrange
        $request = new CashFlowForecastRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            forecastDays: 30
        );

        $forecastData = [
            'inflows' => [
                ['amount' => 10000.50],
                ['amount' => 5000.25],
            ],
            'outflows' => [
                ['amount' => 3000.75],
            ],
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashFlowForecast')
            ->with('tenant-001', '2024-Q1')
            ->willReturn($forecastData);

        // Act
        $result = $this->service->generateForecast($request);

        // Assert
        $this->assertInstanceOf(CashFlowForecastResult::class, $result);
        // 15000.75 - 3000.75 = 12000.00
        $this->assertSame('12000', $result->netCashFlow);
    }

    /**
     * Scenario 5: Error handling - forecast generation fails.
     *
     * Tests that when data provider throws exception during forecast
     * generation, a CashFlowException is thrown.
     */
    public function testGenerateForecastThrowsExceptionOnError(): void
    {
        // Arrange
        $request = new CashFlowForecastRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            forecastDays: 30
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashFlowForecast')
            ->with('tenant-001', '2024-Q1')
            ->willThrowException(new \RuntimeException('Forecast service unavailable'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Failed to generate cash flow forecast', $this->anything());

        // Assert
        $this->expectException(CashFlowException::class);

        // Act
        $this->service->generateForecast($request);
    }

    // =========================================================================
    // Test Suite: Edge Cases and Boundary Conditions
    // =========================================================================

    /**
     * Test that positions with available_balance key are handled.
     *
     * Tests the fallback logic in consolidateBalances that checks
     * for 'available_balance' key.
     */
    public function testGetPositionWithAvailableBalanceKey(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: 'account-001',
            currency: 'USD'
        );

        $positionData = [
            'accountId' => 'account-001',
            'accountName' => 'Primary Checking',
            'available_balance' => '15000.00',
            'currency' => 'USD',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashPosition')
            ->with('tenant-001', 'account-001')
            ->willReturn($positionData);

        // Act
        $result = $this->service->getPosition($request);

        // Assert
        $this->assertSame('15000', $result->consolidatedBalance);
    }

    /**
     * Test default currency in request.
     *
     * Tests that the default currency 'USD' is used when not specified.
     */
    public function testGetPositionWithDefaultCurrency(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: 'account-001'
            // currency defaults to 'USD'
        );

        $positionData = [
            'balance' => '10000.00',
            'currency' => 'USD',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashPosition')
            ->with('tenant-001', 'account-001')
            ->willReturn($positionData);

        // Act
        $result = $this->service->getPosition($request);

        // Assert
        $this->assertSame('USD', $result->currency);
    }

    /**
     * Test with asOfDate in request.
     *
     * Tests that the asOfDate is properly passed through to the result.
     */
    public function testGetPositionWithAsOfDate(): void
    {
        // Arrange
        $asOfDate = new \DateTimeImmutable('2024-01-15');
        
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: 'account-001',
            currency: 'USD',
            asOfDate: $asOfDate
        );

        $positionData = [
            'balance' => '10000.00',
            'currency' => 'USD',
        ];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashPosition')
            ->with('tenant-001', 'account-001')
            ->willReturn($positionData);

        // Act
        $result = $this->service->getPosition($request);

        // Assert
        $this->assertSame($asOfDate, $result->asOfDate);
    }

    /**
     * Test rounding of consolidated balance.
     *
     * Tests that the consolidated balance is properly rounded to 2 decimal places.
     */
    public function testConsolidatedBalanceRounding(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: null,
            currency: 'USD'
        );

        $bankAccounts = [
            ['id' => 'account-001'],
            ['id' => 'account-002'],
        ];

        $positionData1 = ['balance' => '10000.333', 'currency' => 'USD'];
        $positionData2 = ['balance' => '5000.667', 'currency' => 'USD'];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with('tenant-001')
            ->willReturn($bankAccounts);

        $this->dataProviderMock
            ->expects($this->exactly(2))
            ->method('getCashPosition')
            ->willReturnMap([
                ['tenant-001', 'account-001', $positionData1],
                ['tenant-001', 'account-002', $positionData2],
            ]);

        // Act
        $result = $this->service->getPosition($request);

        // Assert
        // 10000.333 + 5000.667 = 15001.00 (rounded)
        $this->assertSame('15001', $result->consolidatedBalance);
    }

    /**
     * Test consolidated position with default currency.
     *
     * Tests that the default currency 'USD' is used in consolidated position.
     */
    public function testGetConsolidatedPositionWithDefaultCurrency(): void
    {
        // Arrange
        $tenantId = 'tenant-001';
        
        $bankAccounts = [
            ['id' => 'account-001'],
        ];

        $positionData = ['balance' => '10000.00', 'currency' => 'USD'];

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with($tenantId)
            ->willReturn($bankAccounts);

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashPosition')
            ->willReturn($positionData);

        // Act
        $result = $this->service->getConsolidatedPosition($tenantId);

        // Assert
        $this->assertSame('USD', $result['currency']);
    }

    // =========================================================================
    // Test Suite: Exception Message Validation
    // =========================================================================

    /**
     * Test exception message for cash position retrieval failure.
     *
     * Validates that the exception contains proper context information.
     */
    public function testCashPositionRetrievalFailedExceptionMessage(): void
    {
        // Arrange
        $request = new CashPositionRequest(
            tenantId: 'tenant-001',
            bankAccountId: 'account-001',
            currency: 'USD'
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashPosition')
            ->with('tenant-001', 'account-001')
            ->willThrowException(new \RuntimeException('Timeout'));

        // Assert
        try {
            // Act
            $this->service->getPosition($request);
            $this->fail('Expected CashFlowException to be thrown');
        } catch (CashFlowException $e) {
            // Assert
            $this->assertStringContainsString('account-001', $e->getMessage());
            $context = $e->getContext();
            $this->assertSame('tenant-001', $context['tenant_id']);
            $this->assertSame('account-001', $context['bank_account_id']);
            $this->assertStringContainsString('Timeout', $e->getMessage());
        }
    }

    /**
     * Test exception message for forecast generation failure.
     *
     * Validates that the exception contains proper period and tenant info.
     */
    public function testForecastGenerationFailedExceptionMessage(): void
    {
        // Arrange
        $request = new CashFlowForecastRequest(
            tenantId: 'tenant-001',
            periodId: '2024-Q1',
            forecastDays: 30
        );

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getCashFlowForecast')
            ->with('tenant-001', '2024-Q1')
            ->willThrowException(new \RuntimeException('Service unavailable'));

        // Assert
        try {
            // Act
            $this->service->generateForecast($request);
            $this->fail('Expected CashFlowException to be thrown');
        } catch (CashFlowException $e) {
            // Assert
            $this->assertStringContainsString('2024-Q1', $e->getMessage());
            $context = $e->getContext();
            $this->assertSame('tenant-001', $context['tenant_id']);
            $this->assertStringContainsString('Service unavailable', $e->getMessage());
        }
    }

    /**
     * Test consolidated position exception contains tenant info.
     */
    public function testConsolidatedPositionExceptionMessage(): void
    {
        // Arrange
        $tenantId = 'tenant-001';

        $this->dataProviderMock
            ->expects($this->once())
            ->method('getBankAccounts')
            ->with($tenantId)
            ->willThrowException(new \RuntimeException('Database error'));

        // Assert
        try {
            // Act
            $this->service->getConsolidatedPosition($tenantId);
            $this->fail('Expected CashFlowException to be thrown');
        } catch (CashFlowException $e) {
            // Assert - check message contains account info
            $this->assertStringContainsString('all', $e->getMessage());
            // Check context has tenant_id
            $context = $e->getContext();
            $this->assertSame('tenant-001', $context['tenant_id']);
        }
    }
}
