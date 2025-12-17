<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Exceptions;

use Nexus\KycVerification\Enums\RiskLevel;

/**
 * Exception thrown when a party is classified as high risk.
 */
class HighRiskPartyException extends KycVerificationException
{
    private string $partyId;

    private RiskLevel $riskLevel;

    private int $riskScore;

    /**
     * @var array<string>
     */
    private array $riskFactors;

    /**
     * @param array<string> $riskFactors
     */
    public function __construct(
        string $partyId,
        RiskLevel $riskLevel,
        int $riskScore,
        array $riskFactors = [],
        ?\Throwable $previous = null
    ) {
        $this->partyId = $partyId;
        $this->riskLevel = $riskLevel;
        $this->riskScore = $riskScore;
        $this->riskFactors = $riskFactors;

        $factorsStr = empty($riskFactors) ? '' : ' Factors: ' . implode(', ', $riskFactors);

        parent::__construct(
            sprintf(
                'Party %s is classified as %s (score: %d).%s',
                $partyId,
                $riskLevel->label(),
                $riskScore,
                $factorsStr
            ),
            0,
            $previous
        );
    }

    public function getPartyId(): string
    {
        return $this->partyId;
    }

    public function getRiskLevel(): RiskLevel
    {
        return $this->riskLevel;
    }

    public function getRiskScore(): int
    {
        return $this->riskScore;
    }

    /**
     * @return array<string>
     */
    public function getRiskFactors(): array
    {
        return $this->riskFactors;
    }

    /**
     * Check if party is prohibited from transactions
     */
    public function isProhibited(): bool
    {
        return $this->riskLevel === RiskLevel::PROHIBITED;
    }

    /**
     * Check if senior approval is required
     */
    public function requiresSeniorApproval(): bool
    {
        return $this->riskLevel->requiresSeniorApproval();
    }

    /**
     * Create for prohibited party
     * 
     * @param array<string> $reasons
     */
    public static function prohibited(string $partyId, array $reasons = []): self
    {
        return new self(
            $partyId,
            RiskLevel::PROHIBITED,
            100,
            $reasons
        );
    }
}
