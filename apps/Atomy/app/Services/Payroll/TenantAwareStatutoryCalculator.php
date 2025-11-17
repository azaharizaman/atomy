<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use Nexus\Payroll\Contracts\PayloadInterface;
use Nexus\Payroll\Contracts\DeductionResultInterface;
use Nexus\Payroll\Exceptions\PayrollException;

/**
 * Tenant-Aware Statutory Calculator
 * 
 * This service loads the appropriate country-specific statutory calculator
 * based on the tenant's configuration. It acts as a registry/factory for
 * country-specific implementations.
 * 
 * Country-specific calculators should be registered in the service provider
 * or configuration file.
 */
class TenantAwareStatutoryCalculator implements StatutoryCalculatorInterface
{
    private array $calculators = [];
    private ?string $defaultCountryCode = null;

    /**
     * Register a country-specific calculator
     */
    public function registerCalculator(string $countryCode, StatutoryCalculatorInterface $calculator): void
    {
        $this->calculators[strtoupper($countryCode)] = $calculator;
    }

    /**
     * Set the default country code to use
     */
    public function setDefaultCountryCode(string $countryCode): void
    {
        $this->defaultCountryCode = strtoupper($countryCode);
    }

    /**
     * Get the calculator for a specific country code
     */
    public function getCalculatorForCountry(string $countryCode): StatutoryCalculatorInterface
    {
        $code = strtoupper($countryCode);
        
        if (!isset($this->calculators[$code])) {
            throw new PayrollException("No statutory calculator registered for country: {$countryCode}");
        }
        
        return $this->calculators[$code];
    }

    public function calculate(PayloadInterface $payload): DeductionResultInterface
    {
        // Get country code from payload metadata or use default
        $metadata = $payload->getMetadata();
        $countryCode = $metadata['country_code'] ?? $this->defaultCountryCode;
        
        if (!$countryCode) {
            throw new PayrollException('Country code not specified in payload and no default set');
        }
        
        $calculator = $this->getCalculatorForCountry($countryCode);
        
        return $calculator->calculate($payload);
    }

    public function getSupportedCountryCode(): string
    {
        // This multi-country calculator doesn't have a single country code
        // Return the default or the first registered
        if ($this->defaultCountryCode) {
            return $this->defaultCountryCode;
        }
        
        if (!empty($this->calculators)) {
            return array_key_first($this->calculators);
        }
        
        throw new PayrollException('No calculators registered');
    }

    public function getRequiredEmployeeFields(): array
    {
        // Return union of all registered calculators' required fields
        $fields = [];
        foreach ($this->calculators as $calculator) {
            $fields = array_merge($fields, $calculator->getRequiredEmployeeFields());
        }
        return array_unique($fields);
    }

    public function getRequiredCompanyFields(): array
    {
        // Return union of all registered calculators' required fields
        $fields = [];
        foreach ($this->calculators as $calculator) {
            $fields = array_merge($fields, $calculator->getRequiredCompanyFields());
        }
        return array_unique($fields);
    }

    public function validatePayload(PayloadInterface $payload): void
    {
        $metadata = $payload->getMetadata();
        $countryCode = $metadata['country_code'] ?? $this->defaultCountryCode;
        
        if (!$countryCode) {
            throw new PayrollException('Country code not specified in payload and no default set');
        }
        
        $calculator = $this->getCalculatorForCountry($countryCode);
        $calculator->validatePayload($payload);
    }

    /**
     * Get list of all registered country codes
     */
    public function getRegisteredCountries(): array
    {
        return array_keys($this->calculators);
    }
}
