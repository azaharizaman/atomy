<?php

declare(strict_types=1);

namespace Nexus\Party\ValueObjects;

/**
 * Tax Identity value object.
 * 
 * Represents a tax identification number issued by a government authority.
 * Immutable to ensure data integrity.
 */
final readonly class TaxIdentity
{
    /**
     * Create a new tax identity.
     *
     * @param string $country ISO 3166-1 alpha-3 country code (e.g., 'MYS', 'SGP', 'USA')
     * @param string $number The tax identification number
     * @param \DateTimeImmutable|null $issueDate Date when the tax ID was issued
     * @param \DateTimeImmutable|null $expiryDate Expiry date (if applicable)
     * @param string|null $type Type of tax ID (e.g., 'VAT', 'GST', 'EIN', 'SSN')
     * 
     * @throws \InvalidArgumentException If validation fails
     */
    public function __construct(
        public string $country,
        public string $number,
        public ?\DateTimeImmutable $issueDate = null,
        public ?\DateTimeImmutable $expiryDate = null,
        public ?string $type = null
    ) {
        $this->validateCountry($country);
        $this->validateNumber($number);
        
        if ($this->issueDate && $this->expiryDate && $this->expiryDate <= $this->issueDate) {
            throw new \InvalidArgumentException('Expiry date must be after issue date');
        }
    }
    
    /**
     * Validate country code format.
     */
    private function validateCountry(string $country): void
    {
        if (!preg_match('/^[A-Z]{3}$/', $country)) {
            throw new \InvalidArgumentException(
                "Invalid country code format. Expected ISO 3166-1 alpha-3 (e.g., 'MYS'), got: {$country}"
            );
        }
    }
    
    /**
     * Validate tax number is not empty.
     */
    private function validateNumber(string $number): void
    {
        $trimmed = trim($number);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Tax identification number cannot be empty');
        }
        
        if (strlen($trimmed) > 100) {
            throw new \InvalidArgumentException('Tax identification number cannot exceed 100 characters');
        }
    }
    
    /**
     * Check if the tax identity is expired.
     */
    public function isExpired(?\DateTimeImmutable $asOf = null): bool
    {
        $asOf = $asOf ?? new \DateTimeImmutable();
        return $this->expiryDate !== null && $this->expiryDate < $asOf;
    }
    
    /**
     * Check if the tax identity is valid on a given date.
     */
    public function isValidOn(\DateTimeImmutable $date): bool
    {
        if ($this->issueDate && $date < $this->issueDate) {
            return false;
        }
        
        if ($this->expiryDate && $date >= $this->expiryDate) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get formatted tax identity string.
     */
    public function format(): string
    {
        $formatted = "{$this->country}: {$this->number}";
        
        if ($this->type) {
            $formatted = "{$this->type} - {$formatted}";
        }
        
        return $formatted;
    }
    
    /**
     * Convert to array representation.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'country' => $this->country,
            'number' => $this->number,
            'issue_date' => $this->issueDate?->format('Y-m-d'),
            'expiry_date' => $this->expiryDate?->format('Y-m-d'),
            'type' => $this->type,
        ];
    }
}
