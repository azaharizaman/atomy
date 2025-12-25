<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\Common\Contracts\ClockInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\RailConfigurationInterface;
use Nexus\PaymentRails\Contracts\RailTransactionPersistInterface;
use Nexus\PaymentRails\Contracts\RailTransactionQueryInterface;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\DTOs\WireTransferRequest;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\WireType;
use Nexus\PaymentRails\Exceptions\RailUnavailableException;
use Nexus\PaymentRails\Exceptions\WireValidationException;
use Nexus\PaymentRails\Rails\WireRail;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use Nexus\PaymentRails\ValueObjects\SwiftCode;
use PHPUnit\Framework\TestCase;

final class WireRailTest extends TestCase
{
    public function test_initiate_transfer_persists_transaction_and_returns_pending_result(): void
    {
        $clock = new FrozenClock(new \DateTimeImmutable('2025-01-06 10:00:00'));

        $configuration = $this->createMock(RailConfigurationInterface::class);
        $configuration->method('isEnabled')->with(RailType::WIRE)->willReturn(true);
        $configuration->method('getCutoffTimes')->with(RailType::WIRE)->willReturn([]);

        $transactionQuery = $this->createMock(RailTransactionQueryInterface::class);
        $transactionPersist = $this->createMock(RailTransactionPersistInterface::class);

        $transactionPersist
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (RailTransactionResult $result): bool {
                self::assertSame(RailType::WIRE, $result->railType);
                self::assertSame('pending', $result->status);
                self::assertSame(12345, $result->amount->getAmountInMinorUnits());
                self::assertSame('USD', $result->amount->getCurrency());
                self::assertArrayHasKey('wire_type', $result->metadata);
                self::assertSame('domestic', $result->metadata['wire_type']);

                return true;
            }))
            ->willReturnCallback(static fn (RailTransactionResult $result): RailTransactionResult => $result);

        $rail = new WireRail(
            configuration: $configuration,
            transactionQuery: $transactionQuery,
            transactionPersist: $transactionPersist,
            logger: new \Psr\Log\NullLogger(),
            clock: $clock,
        );

        $request = WireTransferRequest::domestic(
            amount: new Money(12345, 'USD'),
            beneficiaryName: 'Jane Beneficiary',
            beneficiaryAccountNumber: '123456789',
            beneficiaryBankName: 'Acme Bank',
            beneficiaryRoutingNumber: RoutingNumber::fromString('021000021'),
            paymentReference: 'INV-1001',
        );

        $result = $rail->initiateTransfer($request);

        self::assertSame('pending', $result->status);
        self::assertSame(WireType::DOMESTIC, $result->wireType);
        self::assertSame('USD', $result->amount->getCurrency());
        self::assertSame(12345, $result->amount->getAmountInMinorUnits());
        self::assertNotEmpty($result->transferId);
    }

    public function test_initiate_transfer_throws_when_unavailable(): void
    {
        $clock = new FrozenClock(new \DateTimeImmutable('2025-01-04 10:00:00')); // Saturday

        $configuration = $this->createMock(RailConfigurationInterface::class);
        $configuration->method('isEnabled')->with(RailType::WIRE)->willReturn(false);

        $transactionQuery = $this->createMock(RailTransactionQueryInterface::class);
        $transactionPersist = $this->createMock(RailTransactionPersistInterface::class);
        $transactionPersist->expects(self::never())->method('save');

        $rail = new WireRail(
            configuration: $configuration,
            transactionQuery: $transactionQuery,
            transactionPersist: $transactionPersist,
            logger: new \Psr\Log\NullLogger(),
            clock: $clock,
        );

        $request = WireTransferRequest::domestic(
            amount: new Money(12345, 'USD'),
            beneficiaryName: 'Jane Beneficiary',
            beneficiaryAccountNumber: '123456789',
            beneficiaryBankName: 'Acme Bank',
            beneficiaryRoutingNumber: RoutingNumber::fromString('021000021'),
            paymentReference: 'INV-1001',
        );

        $this->expectException(RailUnavailableException::class);
        $rail->initiateTransfer($request);
    }

    public function test_initiate_transfer_throws_validation_exception_for_invalid_request(): void
    {
        $clock = new FrozenClock(new \DateTimeImmutable('2025-01-06 10:00:00'));

        $configuration = $this->createMock(RailConfigurationInterface::class);
        $configuration->method('isEnabled')->with(RailType::WIRE)->willReturn(true);
        $configuration->method('getCutoffTimes')->with(RailType::WIRE)->willReturn([]);

        $transactionQuery = $this->createMock(RailTransactionQueryInterface::class);
        $transactionPersist = $this->createMock(RailTransactionPersistInterface::class);

        $rail = new WireRail(
            configuration: $configuration,
            transactionQuery: $transactionQuery,
            transactionPersist: $transactionPersist,
            logger: new \Psr\Log\NullLogger(),
            clock: $clock,
        );

        // Domestic wire missing routing number
        $request = new WireTransferRequest(
            amount: new Money(12345, 'USD'),
            wireType: WireType::DOMESTIC,
            beneficiaryName: 'Jane Beneficiary',
            beneficiaryAccountNumber: '123456789',
            beneficiaryBankName: 'Acme Bank',
        );

        $this->expectException(WireValidationException::class);
        $rail->initiateTransfer($request);
    }

    public function test_get_transaction_status_returns_failure_when_missing(): void
    {
        $configuration = $this->createMock(RailConfigurationInterface::class);
        $transactionQuery = $this->createMock(RailTransactionQueryInterface::class);
        $transactionPersist = $this->createMock(RailTransactionPersistInterface::class);

        $transactionQuery->method('findById')->with('tx-404')->willReturn(null);

        $rail = new WireRail(
            configuration: $configuration,
            transactionQuery: $transactionQuery,
            transactionPersist: $transactionPersist,
            logger: new \Psr\Log\NullLogger(),
            clock: new FrozenClock(new \DateTimeImmutable('2025-01-06 10:00:00')),
        );

        $result = $rail->getTransactionStatus('tx-404');

        self::assertSame('failed', $result->status);
        self::assertSame(['Transaction not found'], $result->errors);
    }

    public function test_cancel_transaction_updates_status_when_pending(): void
    {
        $configuration = $this->createMock(RailConfigurationInterface::class);
        $transactionQuery = $this->createMock(RailTransactionQueryInterface::class);
        $transactionPersist = $this->createMock(RailTransactionPersistInterface::class);

        $transactionQuery
            ->method('findById')
            ->with('tx-1')
            ->willReturn(new RailTransactionResult(
                transactionId: 'tx-1',
                success: true,
                status: 'PENDING',
                railType: RailType::WIRE,
                amount: new Money(100, 'USD'),
                referenceNumber: null,
                errors: [],
                metadata: [],
                fees: null,
                processedAt: new \DateTimeImmutable('2025-01-06 10:00:00'),
                settledAt: null,
                expectedSettlementDate: null,
            ));

        $transactionPersist
            ->expects(self::once())
            ->method('updateStatus')
            ->with('tx-1', 'RECALL_REQUESTED');

        $rail = new WireRail(
            configuration: $configuration,
            transactionQuery: $transactionQuery,
            transactionPersist: $transactionPersist,
            logger: new \Psr\Log\NullLogger(),
            clock: new FrozenClock(new \DateTimeImmutable('2025-01-06 10:00:00')),
        );

        self::assertTrue($rail->cancelTransaction('tx-1', 'User requested cancellation'));
    }

    public function test_get_estimated_fee_varies_by_type_and_urgency(): void
    {
        $configuration = $this->createMock(RailConfigurationInterface::class);
        $transactionQuery = $this->createMock(RailTransactionQueryInterface::class);
        $transactionPersist = $this->createMock(RailTransactionPersistInterface::class);

        $rail = new WireRail(
            configuration: $configuration,
            transactionQuery: $transactionQuery,
            transactionPersist: $transactionPersist,
            logger: new \Psr\Log\NullLogger(),
            clock: new FrozenClock(new \DateTimeImmutable('2025-01-06 10:00:00')),
        );

        $domestic = WireTransferRequest::domestic(
            amount: new Money(10000, 'USD'),
            beneficiaryName: 'Jane Beneficiary',
            beneficiaryAccountNumber: '123456789',
            beneficiaryBankName: 'Acme Bank',
            beneficiaryRoutingNumber: RoutingNumber::fromString('021000021'),
        );

        $international = WireTransferRequest::international(
            amount: new Money(10000, 'USD'),
            beneficiaryName: 'Jane Beneficiary',
            beneficiaryAccountNumber: '123456789',
            beneficiaryBankName: 'Acme Bank',
            beneficiarySwiftCode: SwiftCode::fromString('DEUTDEFF'),
            beneficiaryCountry: 'DE',
            beneficiaryIban: null,
            beneficiaryAddress: '1 Main St',
        );

        self::assertSame(2500, $rail->getEstimatedFee($domestic)->getAmountInMinorUnits());
        self::assertSame(5000, $rail->getEstimatedFee($domestic->asUrgent())->getAmountInMinorUnits());

        self::assertSame(4500, $rail->getEstimatedFee($international)->getAmountInMinorUnits());
        self::assertSame(9000, $rail->getEstimatedFee($international->asUrgent())->getAmountInMinorUnits());
    }
}

final class FrozenClock implements ClockInterface
{
    public function __construct(
        private readonly \DateTimeImmutable $now
    ) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
