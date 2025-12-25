<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\PaymentRails\Enums\RailType;

/**
 * Contract for payment rail configuration.
 */
interface RailConfigurationInterface
{
    /**
     * Get configuration for a specific rail type.
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(RailType $railType): array;

    /**
     * Get a specific configuration value.
     */
    public function get(RailType $railType, string $key, mixed $default = null): mixed;

    /**
     * Check if a rail type is enabled.
     */
    public function isEnabled(RailType $railType): bool;

    /**
     * Get all enabled rail types.
     *
     * @return array<RailType>
     */
    public function getEnabledRails(): array;

    /**
     * Get the default rail type.
     */
    public function getDefaultRail(): RailType;

    /**
     * Get the originator/company information.
     *
     * @return array<string, string>
     */
    public function getOriginatorInfo(): array;

    /**
     * Get the ACH company ID.
     */
    public function getAchCompanyId(): string;

    /**
     * Get the immediate destination (bank routing number).
     */
    public function getImmediateDestination(): string;

    /**
     * Get the immediate origin (company routing number).
     */
    public function getImmediateOrigin(): string;

    /**
     * Get cutoff time configuration.
     *
     * @return array<string, \DateTimeImmutable>
     */
    public function getCutoffTimes(RailType $railType): array;
}
