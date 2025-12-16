<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Exceptions;

/**
 * Exception thrown when beneficial ownership validation fails.
 */
class BeneficialOwnershipException extends KycVerificationException
{
    private string $partyId;

    /**
     * @var array<string>
     */
    private array $validationErrors;

    /**
     * @param array<string> $validationErrors
     */
    public function __construct(
        string $partyId,
        array $validationErrors,
        ?\Throwable $previous = null
    ) {
        $this->partyId = $partyId;
        $this->validationErrors = $validationErrors;

        parent::__construct(
            sprintf(
                'Beneficial ownership validation failed for party %s: %s',
                $partyId,
                implode('; ', $validationErrors)
            ),
            0,
            $previous
        );
    }

    public function getPartyId(): string
    {
        return $this->partyId;
    }

    /**
     * @return array<string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Create for missing UBO declaration
     */
    public static function missingDeclaration(string $partyId): self
    {
        return new self($partyId, ['UBO declaration is missing']);
    }

    /**
     * Create for incomplete ownership chain
     */
    public static function incompleteOwnershipChain(string $partyId, float $identifiedPercentage): self
    {
        return new self($partyId, [
            sprintf(
                'Incomplete ownership chain: only %.2f%% of ownership identified (minimum 100%% required)',
                $identifiedPercentage
            ),
        ]);
    }

    /**
     * Create for unverified UBO
     */
    public static function unverifiedUbo(string $partyId, string $uboName): self
    {
        return new self($partyId, [
            sprintf('Beneficial owner "%s" has not been verified', $uboName),
        ]);
    }

    /**
     * Create for circular ownership detected
     */
    public static function circularOwnership(string $partyId): self
    {
        return new self($partyId, ['Circular ownership structure detected']);
    }
}
