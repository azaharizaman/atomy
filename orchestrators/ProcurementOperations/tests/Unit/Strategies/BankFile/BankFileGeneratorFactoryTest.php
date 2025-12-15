<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Strategies\BankFile;

use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\Enums\NachaSecCode;
use Nexus\ProcurementOperations\Enums\PositivePayFormat;
use Nexus\ProcurementOperations\Exceptions\UnsupportedBankFileFormatException;
use Nexus\ProcurementOperations\Strategies\BankFile\BankFileGeneratorFactory;
use Nexus\ProcurementOperations\Strategies\BankFile\NachaFileGenerator;
use Nexus\ProcurementOperations\Strategies\BankFile\PositivePayGenerator;
use Nexus\ProcurementOperations\Strategies\BankFile\SwiftMt101Generator;
use Nexus\ProcurementOperations\ValueObjects\NachaConfiguration;
use Nexus\ProcurementOperations\ValueObjects\PositivePayConfiguration;
use Nexus\ProcurementOperations\ValueObjects\SwiftMt101Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(BankFileGeneratorFactory::class)]
final class BankFileGeneratorFactoryTest extends TestCase
{
    private BankFileGeneratorFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new BankFileGeneratorFactory(new NullLogger());
    }

    #[Test]
    public function it_creates_nacha_generator(): void
    {
        $config = new NachaConfiguration(
            immediateDestination: '021000021', // Valid routing number
            immediateOrigin: '123456789',
            immediateDestinationName: 'DEST BANK',
            immediateOriginName: 'TEST COMPANY',
            companyName: 'TEST COMPANY',
            companyId: '1234567890',
        );

        $generator = $this->factory->create(BankFileFormat::NACHA, $config);

        $this->assertInstanceOf(NachaFileGenerator::class, $generator);
        $this->assertSame(BankFileFormat::NACHA, $generator->getFormat());
    }

    #[Test]
    public function it_creates_positive_pay_generator(): void
    {
        $config = new PositivePayConfiguration(
            bankAccountNumber: '123456789012',
            bankRoutingNumber: '021000021', // Valid routing number
            format: PositivePayFormat::STANDARD_CSV,
        );

        $generator = $this->factory->create(BankFileFormat::POSITIVE_PAY, $config);

        $this->assertInstanceOf(PositivePayGenerator::class, $generator);
        $this->assertSame(BankFileFormat::POSITIVE_PAY, $generator->getFormat());
    }

    #[Test]
    public function it_creates_swift_mt101_generator(): void
    {
        $config = new SwiftMt101Configuration(
            senderBic: 'ABCDUS33XXX',
            orderingCustomerAccount: '123456789012',
            orderingCustomerName: 'TEST COMPANY',
        );

        $generator = $this->factory->create(BankFileFormat::SWIFT_MT101, $config);

        $this->assertInstanceOf(SwiftMt101Generator::class, $generator);
        $this->assertSame(BankFileFormat::SWIFT_MT101, $generator->getFormat());
    }

    #[Test]
    public function it_throws_exception_for_iso20022_format(): void
    {
        $config = new NachaConfiguration(
            immediateDestination: '021000021',
            immediateOrigin: '123456789',
            immediateDestinationName: 'DEST BANK',
            immediateOriginName: 'TEST COMPANY',
            companyName: 'TEST COMPANY',
            companyId: '1234567890',
        );

        $this->expectException(UnsupportedBankFileFormatException::class);
        $this->expectExceptionMessage('ISO20022');

        $this->factory->create(BankFileFormat::ISO20022, $config);
    }

    #[Test]
    public function it_throws_exception_for_bai2_format(): void
    {
        $config = new NachaConfiguration(
            immediateDestination: '021000021',
            immediateOrigin: '123456789',
            immediateDestinationName: 'DEST BANK',
            immediateOriginName: 'TEST COMPANY',
            companyName: 'TEST COMPANY',
            companyId: '1234567890',
        );

        $this->expectException(UnsupportedBankFileFormatException::class);
        $this->expectExceptionMessage('BAI2');

        $this->factory->create(BankFileFormat::BAI2, $config);
    }

    #[Test]
    public function it_throws_exception_for_wrong_configuration_type(): void
    {
        $config = new PositivePayConfiguration(
            bankAccountNumber: '123456789012',
            bankRoutingNumber: '021000021',
            format: PositivePayFormat::STANDARD_CSV,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NachaConfiguration');

        $this->factory->create(BankFileFormat::NACHA, $config);
    }

    #[Test]
    public function it_creates_nacha_generator_directly(): void
    {
        $config = new NachaConfiguration(
            immediateDestination: '021000021',
            immediateOrigin: '123456789',
            immediateDestinationName: 'DEST BANK',
            immediateOriginName: 'TEST COMPANY',
            companyName: 'TEST COMPANY',
            companyId: '1234567890',
        );

        $generator = $this->factory->createNachaGenerator($config);

        $this->assertInstanceOf(NachaFileGenerator::class, $generator);
    }

    #[Test]
    public function it_creates_positive_pay_generator_directly(): void
    {
        $config = new PositivePayConfiguration(
            bankAccountNumber: '123456789012',
            bankRoutingNumber: '021000021',
            format: PositivePayFormat::STANDARD_CSV,
        );

        $generator = $this->factory->createPositivePayGenerator($config);

        $this->assertInstanceOf(PositivePayGenerator::class, $generator);
    }

    #[Test]
    public function it_creates_swift_mt101_generator_directly(): void
    {
        $config = new SwiftMt101Configuration(
            senderBic: 'ABCDUS33XXX',
            orderingCustomerAccount: '123456789012',
            orderingCustomerName: 'TEST COMPANY',
        );

        $generator = $this->factory->createSwiftMt101Generator($config);

        $this->assertInstanceOf(SwiftMt101Generator::class, $generator);
    }

    #[Test]
    public function it_creates_nacha_for_vendor_payments(): void
    {
        $generator = $this->factory->createNachaForVendorPayments(
            immediateOrigin: '123456789',
            immediateDestination: '021000021',
            companyName: 'TEST COMPANY',
            companyId: '1234567890',
        );

        $this->assertInstanceOf(NachaFileGenerator::class, $generator);
        $this->assertSame(BankFileFormat::NACHA, $generator->getFormat());
    }

    #[Test]
    public function it_creates_positive_pay_for_bank_of_america(): void
    {
        $generator = $this->factory->createPositivePayForBank(
            bankFormat: PositivePayFormat::BANK_OF_AMERICA,
            accountNumber: '123456789012',
        );

        $this->assertInstanceOf(PositivePayGenerator::class, $generator);
    }

    #[Test]
    public function it_creates_positive_pay_for_wells_fargo(): void
    {
        $generator = $this->factory->createPositivePayForBank(
            bankFormat: PositivePayFormat::WELLS_FARGO,
            accountNumber: '123456789012',
        );

        $this->assertInstanceOf(PositivePayGenerator::class, $generator);
    }

    #[Test]
    public function it_creates_positive_pay_for_chase(): void
    {
        $generator = $this->factory->createPositivePayForBank(
            bankFormat: PositivePayFormat::CHASE,
            accountNumber: '123456789012',
        );

        $this->assertInstanceOf(PositivePayGenerator::class, $generator);
    }

    #[Test]
    public function it_creates_positive_pay_for_citi(): void
    {
        $generator = $this->factory->createPositivePayForBank(
            bankFormat: PositivePayFormat::CITI,
            accountNumber: '123456789012',
        );

        $this->assertInstanceOf(PositivePayGenerator::class, $generator);
    }

    #[Test]
    public function it_creates_swift_for_international_payments(): void
    {
        $generator = $this->factory->createSwiftForInternational(
            senderBic: 'ABCDUS33XXX',
            accountNumber: '123456789012',
            companyName: 'TEST COMPANY',
        );

        $this->assertInstanceOf(SwiftMt101Generator::class, $generator);
        $this->assertSame(BankFileFormat::SWIFT_MT101, $generator->getFormat());
    }

    #[Test]
    public function it_returns_supported_formats(): void
    {
        $formats = $this->factory->getSupportedFormats();

        $this->assertContains(BankFileFormat::NACHA, $formats);
        $this->assertContains(BankFileFormat::POSITIVE_PAY, $formats);
        $this->assertContains(BankFileFormat::SWIFT_MT101, $formats);
        $this->assertNotContains(BankFileFormat::ISO20022, $formats);
        $this->assertNotContains(BankFileFormat::BAI2, $formats);
    }

    #[Test]
    public function it_checks_if_format_is_supported(): void
    {
        $this->assertTrue($this->factory->isFormatSupported(BankFileFormat::NACHA));
        $this->assertTrue($this->factory->isFormatSupported(BankFileFormat::POSITIVE_PAY));
        $this->assertTrue($this->factory->isFormatSupported(BankFileFormat::SWIFT_MT101));
        $this->assertFalse($this->factory->isFormatSupported(BankFileFormat::ISO20022));
        $this->assertFalse($this->factory->isFormatSupported(BankFileFormat::BAI2));
    }
}
