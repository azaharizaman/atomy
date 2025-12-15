<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Strategies\BankFile;

use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\Enums\PositivePayFormat;
use Nexus\ProcurementOperations\Exceptions\UnsupportedBankFileFormatException;
use Nexus\ProcurementOperations\ValueObjects\NachaConfiguration;
use Nexus\ProcurementOperations\ValueObjects\PositivePayConfiguration;
use Nexus\ProcurementOperations\ValueObjects\SwiftMt101Configuration;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory for creating bank file generators.
 *
 * Provides a centralized way to instantiate the appropriate generator
 * based on the desired output format.
 *
 * Usage:
 * ```php
 * $factory = new BankFileGeneratorFactory($logger);
 *
 * // Create NACHA generator
 * $nachaConfig = NachaConfiguration::forVendorPayments(...);
 * $generator = $factory->createNachaGenerator($nachaConfig);
 *
 * // Create Positive Pay generator
 * $ppConfig = PositivePayConfiguration::forWellsFargo(...);
 * $generator = $factory->createPositivePayGenerator($ppConfig);
 *
 * // Create from format enum
 * $generator = $factory->create(BankFileFormat::NACHA, $nachaConfig);
 * ```
 */
final readonly class BankFileGeneratorFactory
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Create a generator based on format and configuration.
     *
     * @param NachaConfiguration|PositivePayConfiguration|SwiftMt101Configuration $configuration
     * @throws UnsupportedBankFileFormatException
     */
    public function create(
        BankFileFormat $format,
        NachaConfiguration|PositivePayConfiguration|SwiftMt101Configuration $configuration,
    ): BankFileGeneratorInterface {
        return match ($format) {
            BankFileFormat::NACHA => $this->createNachaGenerator($configuration),
            BankFileFormat::POSITIVE_PAY => $this->createPositivePayGenerator($configuration),
            BankFileFormat::SWIFT_MT101 => $this->createSwiftMt101Generator($configuration),
            BankFileFormat::ISO20022 => throw new UnsupportedBankFileFormatException(
                'ISO20022 format is not yet implemented. Use SWIFT MT101 for international transfers.',
            ),
            BankFileFormat::BAI2 => throw new UnsupportedBankFileFormatException(
                'BAI2 format for bank statements is not supported. Use Positive Pay with BAI2 format for check files.',
            ),
        };
    }

    /**
     * Create a NACHA ACH file generator.
     *
     * @param NachaConfiguration $configuration
     */
    public function createNachaGenerator(
        NachaConfiguration|PositivePayConfiguration|SwiftMt101Configuration $configuration,
    ): NachaFileGenerator {
        if (!$configuration instanceof NachaConfiguration) {
            throw new \InvalidArgumentException(
                'NACHA generator requires NachaConfiguration, got ' . get_class($configuration),
            );
        }

        return new NachaFileGenerator($configuration, $this->logger);
    }

    /**
     * Create a Positive Pay file generator.
     *
     * @param PositivePayConfiguration $configuration
     */
    public function createPositivePayGenerator(
        NachaConfiguration|PositivePayConfiguration|SwiftMt101Configuration $configuration,
    ): PositivePayGenerator {
        if (!$configuration instanceof PositivePayConfiguration) {
            throw new \InvalidArgumentException(
                'Positive Pay generator requires PositivePayConfiguration, got ' . get_class($configuration),
            );
        }

        return new PositivePayGenerator($configuration, $this->logger);
    }

    /**
     * Create a SWIFT MT101 file generator.
     *
     * @param SwiftMt101Configuration $configuration
     */
    public function createSwiftMt101Generator(
        NachaConfiguration|PositivePayConfiguration|SwiftMt101Configuration $configuration,
    ): SwiftMt101Generator {
        if (!$configuration instanceof SwiftMt101Configuration) {
            throw new \InvalidArgumentException(
                'SWIFT MT101 generator requires SwiftMt101Configuration, got ' . get_class($configuration),
            );
        }

        return new SwiftMt101Generator($configuration, $this->logger);
    }

    /**
     * Create a NACHA generator with quick configuration.
     *
     * Convenience method for common use cases.
     */
    public function createNachaForVendorPayments(
        string $immediateOrigin,
        string $immediateDestination,
        string $companyName,
        string $companyId,
        string $originName = '',
        string $destinationName = '',
    ): NachaFileGenerator {
        $configuration = NachaConfiguration::forVendorPayments(
            immediateDestination: $immediateDestination,
            immediateOrigin: $immediateOrigin,
            destinationName: $destinationName ?: 'DEST BANK',
            originName: $originName ?: $companyName,
            companyName: $companyName,
            companyId: $companyId,
        );

        return new NachaFileGenerator($configuration, $this->logger);
    }

    /**
     * Create a Positive Pay generator for a specific bank.
     *
     * Convenience method for bank-specific formats.
     */
    public function createPositivePayForBank(
        PositivePayFormat $bankFormat,
        string $accountNumber,
        ?string $bankId = null,
    ): PositivePayGenerator {
        $configuration = match ($bankFormat) {
            PositivePayFormat::BANK_OF_AMERICA => PositivePayConfiguration::bankOfAmerica(
                accountNumber: $accountNumber,
                routingNumber: $bankId ?? '000000000',
                name: '',
                id: '',
            ),
            PositivePayFormat::WELLS_FARGO => PositivePayConfiguration::wellsFargo(
                accountNumber: $accountNumber,
                routingNumber: $bankId ?? '000000000',
                name: '',
                id: '',
            ),
            PositivePayFormat::CHASE => PositivePayConfiguration::chase(
                accountNumber: $accountNumber,
                routingNumber: $bankId ?? '000000000',
                name: '',
                id: '',
            ),
            PositivePayFormat::CITI => PositivePayConfiguration::citi(
                accountNumber: $accountNumber,
                routingNumber: $bankId ?? '000000000',
                name: '',
                id: '',
            ),
            default => new PositivePayConfiguration(
                bankAccountNumber: $accountNumber,
                bankRoutingNumber: $bankId ?? '000000000',
                format: $bankFormat,
            ),
        };

        return new PositivePayGenerator($configuration, $this->logger);
    }

    /**
     * Create a SWIFT MT101 generator for international payments.
     *
     * Convenience method for international wire transfers.
     */
    public function createSwiftForInternational(
        string $senderBic,
        string $accountNumber,
        string $companyName,
        ?string $accountServicingBic = null,
    ): SwiftMt101Generator {
        $configuration = SwiftMt101Configuration::forInternationalPayments(
            senderBic: $senderBic,
            accountNumber: $accountNumber,
            companyName: $companyName,
            accountServicingBic: $accountServicingBic,
        );

        return new SwiftMt101Generator($configuration, $this->logger);
    }

    /**
     * Get all supported formats.
     *
     * @return array<BankFileFormat>
     */
    public function getSupportedFormats(): array
    {
        return [
            BankFileFormat::NACHA,
            BankFileFormat::POSITIVE_PAY,
            BankFileFormat::SWIFT_MT101,
        ];
    }

    /**
     * Check if a format is supported.
     */
    public function isFormatSupported(BankFileFormat $format): bool
    {
        return in_array($format, $this->getSupportedFormats(), true);
    }
}
