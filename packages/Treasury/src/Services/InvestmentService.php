<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\InvestmentInterface;
use Nexus\Treasury\Contracts\InvestmentPersistInterface;
use Nexus\Treasury\Contracts\InvestmentQueryInterface;
use Nexus\Treasury\Entities\Investment;
use Nexus\Treasury\Enums\InvestmentStatus;
use Nexus\Treasury\Enums\InvestmentType;
use Nexus\Treasury\Exceptions\InvestmentNotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class InvestmentService
{
    public function __construct(
        private InvestmentQueryInterface $query,
        private InvestmentPersistInterface $persist,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function record(
        string $tenantId,
        InvestmentType $type,
        string $name,
        Money $principal,
        float $interestRate,
        DateTimeImmutable $maturityDate,
        string $bankAccountId,
        ?string $referenceNumber = null,
        ?string $description = null
    ): InvestmentInterface {
        $now = new DateTimeImmutable();
        $maturityAmount = $this->calculateMaturityAmount($principal, $interestRate, $now, $maturityDate);

        $investment = new Investment(
            id: $this->generateId(),
            tenantId: $tenantId,
            investmentType: $type,
            name: $name,
            description: $description,
            principalAmount: $principal,
            interestRate: $interestRate,
            maturityDate: $maturityDate,
            investmentDate: $now,
            status: InvestmentStatus::ACTIVE,
            maturityAmount: $maturityAmount,
            accruedInterest: Money::of(0, $principal->getCurrency()),
            bankAccountId: $bankAccountId,
            referenceNumber: $referenceNumber,
            createdAt: $now,
            updatedAt: $now
        );

        $this->persist->save($investment);

        $this->logger->info('Investment recorded', [
            'investment_id' => $investment->getId(),
            'tenant_id' => $tenantId,
            'type' => $type->value,
            'principal' => $principal->format(),
        ]);

        return $investment;
    }

    public function mature(string $investmentId): InvestmentInterface
    {
        $investment = $this->query->findOrFail($investmentId);

        if ($investment->isMatured()) {
            return $investment;
        }

        $now = new DateTimeImmutable();
        $accruedInterest = $this->calculateAccruedInterest(
            $investment->getPrincipalAmount(),
            $investment->getInterestRate(),
            $investment->getInvestmentDate(),
            $now
        );

        $matured = new Investment(
            id: $investment->getId(),
            tenantId: $investment->getTenantId(),
            investmentType: $investment->getInvestmentType(),
            name: $investment->getName(),
            description: $investment->getDescription(),
            principalAmount: $investment->getPrincipalAmount(),
            interestRate: $investment->getInterestRate(),
            maturityDate: $investment->getMaturityDate(),
            investmentDate: $investment->getInvestmentDate(),
            status: InvestmentStatus::MATURED,
            maturityAmount: $investment->getMaturityAmount(),
            accruedInterest: $accruedInterest,
            bankAccountId: $investment->getBankAccountId(),
            referenceNumber: $investment->getReferenceNumber(),
            createdAt: $investment->getCreatedAt(),
            updatedAt: $now
        );

        $this->persist->save($matured);

        $this->logger->info('Investment matured', [
            'investment_id' => $investmentId,
            'maturity_amount' => $matured->getMaturityAmount()->format(),
        ]);

        return $matured;
    }

    public function earlyRedeem(string $investmentId, float $penaltyRate = 0.0): InvestmentInterface
    {
        $investment = $this->query->findOrFail($investmentId);

        if (!$investment->isActive()) {
            throw InvestmentNotFoundException::forId($investmentId);
        }

        $now = new DateTimeImmutable();
        $accruedInterest = $this->calculateAccruedInterest(
            $investment->getPrincipalAmount(),
            $investment->getInterestRate(),
            $investment->getInvestmentDate(),
            $now
        );

        if ($penaltyRate > 0) {
            $penalty = $accruedInterest->multiply($penaltyRate / 100);
            $accruedInterest = $accruedInterest->subtract($penalty);
        }

        $redeemed = new Investment(
            id: $investment->getId(),
            tenantId: $investment->getTenantId(),
            investmentType: $investment->getInvestmentType(),
            name: $investment->getName(),
            description: $investment->getDescription(),
            principalAmount: $investment->getPrincipalAmount(),
            interestRate: $investment->getInterestRate(),
            maturityDate: $investment->getMaturityDate(),
            investmentDate: $investment->getInvestmentDate(),
            status: InvestmentStatus::CANCELLED,
            maturityAmount: $investment->getPrincipalAmount()->add($accruedInterest),
            accruedInterest: $accruedInterest,
            bankAccountId: $investment->getBankAccountId(),
            referenceNumber: $investment->getReferenceNumber(),
            createdAt: $investment->getCreatedAt(),
            updatedAt: $now
        );

        $this->persist->save($redeemed);

        $this->logger->info('Investment early redeemed', [
            'investment_id' => $investmentId,
            'penalty_rate' => $penaltyRate,
            'final_amount' => $redeemed->getMaturityAmount()->format(),
        ]);

        return $redeemed;
    }

    public function get(string $investmentId): InvestmentInterface
    {
        return $this->query->findOrFail($investmentId);
    }

    public function getActive(string $tenantId): array
    {
        return $this->query->findActiveByTenantId($tenantId);
    }

    public function getMaturingBetween(
        string $tenantId,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array {
        return $this->query->findMaturingBetween($tenantId, $from, $to);
    }

    public function calculateAccruedInterestFor(string $investmentId, ?DateTimeImmutable $asOfDate = null): Money
    {
        $investment = $this->query->findOrFail($investmentId);
        $date = $asOfDate ?? new DateTimeImmutable();

        return $this->calculateAccruedInterest(
            $investment->getPrincipalAmount(),
            $investment->getInterestRate(),
            $investment->getInvestmentDate(),
            $date
        );
    }

    public function getInvestmentSummary(string $tenantId): array
    {
        $activeInvestments = $this->query->findActiveByTenantId($tenantId);
        $maturedInvestments = $this->query->findMaturedByTenantId($tenantId);
        $totalPrincipal = $this->query->sumPrincipalByTenantId($tenantId);

        $byType = [];
        foreach ($activeInvestments as $investment) {
            $type = $investment->getInvestmentType()->value;
            if (!isset($byType[$type])) {
                $byType[$type] = [
                    'count' => 0,
                    'total_principal' => 0,
                    'avg_rate' => 0,
                ];
            }
            $byType[$type]['count']++;
            $byType[$type]['total_principal'] += $investment->getPrincipalAmount()->getAmount();
            $byType[$type]['avg_rate'] += $investment->getInterestRate();
        }

        foreach ($byType as $type => &$data) {
            $data['avg_rate'] /= $data['count'];
        }

        return [
            'active_count' => count($activeInvestments),
            'matured_count' => count($maturedInvestments),
            'total_principal' => $totalPrincipal,
            'by_type' => $byType,
        ];
    }

    public function processMaturedInvestments(string $tenantId): int
    {
        $maturedInvestments = $this->query->findMaturedByTenantId($tenantId);
        $processedCount = 0;

        foreach ($maturedInvestments as $investment) {
            if ($investment->isActive()) {
                $this->mature($investment->getId());
                $processedCount++;
            }
        }

        if ($processedCount > 0) {
            $this->logger->info('Matured investments processed', [
                'tenant_id' => $tenantId,
                'count' => $processedCount,
            ]);
        }

        return $processedCount;
    }

    private function calculateMaturityAmount(
        Money $principal,
        float $interestRate,
        DateTimeImmutable $investmentDate,
        DateTimeImmutable $maturityDate
    ): Money {
        $days = (int) $investmentDate->diff($maturityDate)->days;
        $years = $days / 365;
        $maturityValue = $principal->getAmount() * (1 + ($interestRate / 100) * $years);

        return Money::of($maturityValue, $principal->getCurrency());
    }

    private function calculateAccruedInterest(
        Money $principal,
        float $interestRate,
        DateTimeImmutable $startDate,
        DateTimeImmutable $asOfDate
    ): Money {
        $days = (int) $startDate->diff($asOfDate)->days;
        $years = $days / 365;
        $accrued = $principal->getAmount() * ($interestRate / 100) * $years;

        return Money::of($accrued, $principal->getCurrency());
    }

    private function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-INV-' . $uuid;
    }
}
