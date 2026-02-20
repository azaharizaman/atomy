<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\IntercompanyLoanPersistInterface;
use Nexus\Treasury\Contracts\IntercompanyLoanQueryInterface;
use Nexus\Treasury\Contracts\IntercompanyTreasuryInterface;
use Nexus\Treasury\Entities\IntercompanyLoan;
use Nexus\Treasury\Exceptions\IntercompanyLoanNotFoundException;
use Nexus\Treasury\Support\InterestCalculator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class IntercompanyTreasuryService
{
    public function __construct(
        private IntercompanyLoanQueryInterface $query,
        private IntercompanyLoanPersistInterface $persist,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function recordLoan(
        string $tenantId,
        string $fromEntityId,
        string $toEntityId,
        Money $principal,
        float $interestRate,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $maturityDate = null,
        ?string $referenceNumber = null,
        ?string $notes = null
    ): IntercompanyTreasuryInterface {
        $now = new DateTimeImmutable();

        $loan = new IntercompanyLoan(
            id: $this->generateId(),
            tenantId: $tenantId,
            fromEntityId: $fromEntityId,
            toEntityId: $toEntityId,
            loanType: 'intercompany',
            principalAmount: $principal,
            interestRate: $interestRate,
            outstandingBalance: $principal,
            startDate: $startDate,
            maturityDate: $maturityDate,
            accruedInterest: Money::of(0, $principal->getCurrency()),
            paymentSchedule: [],
            referenceNumber: $referenceNumber,
            notes: $notes,
            createdAt: $now,
            updatedAt: $now
        );

        $this->persist->save($loan);

        $this->logger->info('Intercompany loan recorded', [
            'loan_id' => $loan->getId(),
            'tenant_id' => $tenantId,
            'from_entity' => $fromEntityId,
            'to_entity' => $toEntityId,
            'principal' => $principal->format(),
        ]);

        return $loan;
    }

    public function recordRepayment(
        string $loanId,
        Money $amount,
        ?DateTimeImmutable $repaymentDate = null
    ): IntercompanyTreasuryInterface {
        $loan = $this->query->findOrFail($loanId);
        $date = $repaymentDate ?? new DateTimeImmutable();

        $accruedInterest = $this->calculateAccruedInterest(
            $loan->getPrincipalAmount(),
            $loan->getInterestRate(),
            $loan->getStartDate(),
            $date
        );

        $totalOutstanding = $loan->getOutstandingBalance()->add($accruedInterest);
        $newOutstanding = $totalOutstanding->subtract($amount);

        if ($newOutstanding->getAmount() < 0) {
            $newOutstanding = Money::of(0, $loan->getPrincipalAmount()->getCurrency());
        }

        $updated = new IntercompanyLoan(
            id: $loan->getId(),
            tenantId: $loan->getTenantId(),
            fromEntityId: $loan->getFromEntityId(),
            toEntityId: $loan->getToEntityId(),
            loanType: $loan->getLoanType(),
            principalAmount: $loan->getPrincipalAmount(),
            interestRate: $loan->getInterestRate(),
            outstandingBalance: $newOutstanding,
            startDate: $loan->getStartDate(),
            maturityDate: $loan->getMaturityDate(),
            accruedInterest: Money::of(0, $loan->getPrincipalAmount()->getCurrency()),
            paymentSchedule: $this->addPaymentToSchedule($loan->getPaymentSchedule(), $amount, $date),
            referenceNumber: $loan->getReferenceNumber(),
            notes: $loan->getNotes(),
            createdAt: $loan->getCreatedAt(),
            updatedAt: $date
        );

        $this->persist->save($updated);

        $this->logger->info('Intercompany loan repayment recorded', [
            'loan_id' => $loanId,
            'amount' => $amount->format(),
            'new_outstanding' => $newOutstanding->format(),
        ]);

        return $updated;
    }

    public function calculateInterest(string $loanId, ?DateTimeImmutable $asOfDate = null): Money
    {
        $loan = $this->query->findOrFail($loanId);
        $date = $asOfDate ?? new DateTimeImmutable();

        return $this->calculateAccruedInterest(
            $loan->getPrincipalAmount(),
            $loan->getInterestRate(),
            $loan->getStartDate(),
            $date
        );
    }

    public function get(string $loanId): IntercompanyTreasuryInterface
    {
        return $this->query->findOrFail($loanId);
    }

    public function getActive(string $tenantId): array
    {
        return $this->query->findActiveByTenantId($tenantId);
    }

    public function getOverdue(string $tenantId): array
    {
        return $this->query->findOverdueByTenantId($tenantId);
    }

    public function getLoansBetweenEntities(string $fromEntityId, string $toEntityId): array
    {
        return $this->query->findBetweenEntities($fromEntityId, $toEntityId);
    }

    public function getLoansByFromEntity(string $entityId): array
    {
        return $this->query->findByFromEntity($entityId);
    }

    public function getLoansByToEntity(string $entityId): array
    {
        return $this->query->findByToEntity($entityId);
    }

    public function getIntercompanyPosition(string $entityId): array
    {
        $loansFrom = $this->query->findByFromEntity($entityId);
        $loansTo = $this->query->findByToEntity($entityId);

        $lentByCurrency = [];
        $borrowedByCurrency = [];
        $interestReceivableByCurrency = [];
        $interestPayableByCurrency = [];

        foreach ($loansFrom as $loan) {
            if ($loan->isActive()) {
                $currency = $loan->getOutstandingBalance()->getCurrency();
                $outstanding = $loan->getOutstandingBalance()->getAmount();
                $interest = $this->calculateInterest($loan->getId())->getAmount();

                if (!isset($lentByCurrency[$currency])) {
                    $lentByCurrency[$currency] = 0.0;
                }
                if (!isset($interestReceivableByCurrency[$currency])) {
                    $interestReceivableByCurrency[$currency] = 0.0;
                }

                $lentByCurrency[$currency] += $outstanding;
                $interestReceivableByCurrency[$currency] += $interest;
            }
        }

        foreach ($loansTo as $loan) {
            if ($loan->isActive()) {
                $currency = $loan->getOutstandingBalance()->getCurrency();
                $outstanding = $loan->getOutstandingBalance()->getAmount();
                $interest = $this->calculateInterest($loan->getId())->getAmount();

                if (!isset($borrowedByCurrency[$currency])) {
                    $borrowedByCurrency[$currency] = 0.0;
                }
                if (!isset($interestPayableByCurrency[$currency])) {
                    $interestPayableByCurrency[$currency] = 0.0;
                }

                $borrowedByCurrency[$currency] += $outstanding;
                $interestPayableByCurrency[$currency] += $interest;
            }
        }

        $allCurrencies = array_unique(array_merge(
            array_keys($lentByCurrency),
            array_keys($borrowedByCurrency)
        ));

        $netByCurrency = [];
        $netInterestByCurrency = [];
        $totalLent = 0.0;
        $totalBorrowed = 0.0;
        $totalInterestReceivable = 0.0;
        $totalInterestPayable = 0.0;

        foreach ($allCurrencies as $currency) {
            $lent = $lentByCurrency[$currency] ?? 0.0;
            $borrowed = $borrowedByCurrency[$currency] ?? 0.0;
            $interestRec = $interestReceivableByCurrency[$currency] ?? 0.0;
            $interestPay = $interestPayableByCurrency[$currency] ?? 0.0;

            $netByCurrency[$currency] = $lent - $borrowed;
            $netInterestByCurrency[$currency] = $interestRec - $interestPay;

            $totalLent += $lent;
            $totalBorrowed += $borrowed;
            $totalInterestReceivable += $interestRec;
            $totalInterestPayable += $interestPay;
        }

        return [
            'entity_id' => $entityId,
            'total_lent' => $totalLent,
            'total_borrowed' => $totalBorrowed,
            'net_position' => $totalLent - $totalBorrowed,
            'interest_receivable' => $totalInterestReceivable,
            'interest_payable' => $totalInterestPayable,
            'net_interest' => $totalInterestReceivable - $totalInterestPayable,
            'total_lent_by_currency' => $lentByCurrency,
            'total_borrowed_by_currency' => $borrowedByCurrency,
            'net_by_currency' => $netByCurrency,
            'interest_receivable_by_currency' => $interestReceivableByCurrency,
            'interest_payable_by_currency' => $interestPayableByCurrency,
            'net_interest_by_currency' => $netInterestByCurrency,
        ];
    }

    public function getIntercompanySummary(string $tenantId): array
    {
        $activeLoans = $this->query->findActiveByTenantId($tenantId);
        $overdueLoans = $this->query->findOverdueByTenantId($tenantId);
        $totalOutstanding = $this->query->sumOutstandingByTenantId($tenantId);

        $byEntity = [];
        foreach ($activeLoans as $loan) {
            $fromEntity = $loan->getFromEntityId();
            $toEntity = $loan->getToEntityId();

            if (!isset($byEntity[$fromEntity])) {
                $byEntity[$fromEntity] = ['lent' => 0, 'borrowed' => 0];
            }
            if (!isset($byEntity[$toEntity])) {
                $byEntity[$toEntity] = ['lent' => 0, 'borrowed' => 0];
            }

            $byEntity[$fromEntity]['lent'] += $loan->getOutstandingBalance()->getAmount();
            $byEntity[$toEntity]['borrowed'] += $loan->getOutstandingBalance()->getAmount();
        }

        return [
            'active_count' => count($activeLoans),
            'overdue_count' => count($overdueLoans),
            'total_outstanding' => $totalOutstanding,
            'by_entity' => $byEntity,
        ];
    }

    public function accrueInterest(string $loanId, ?DateTimeImmutable $asOfDate = null): IntercompanyTreasuryInterface
    {
        $loan = $this->query->findOrFail($loanId);
        $date = $asOfDate ?? new DateTimeImmutable();

        $accruedInterest = $this->calculateAccruedInterest(
            $loan->getPrincipalAmount(),
            $loan->getInterestRate(),
            $loan->getStartDate(),
            $date
        );

        $updated = new IntercompanyLoan(
            id: $loan->getId(),
            tenantId: $loan->getTenantId(),
            fromEntityId: $loan->getFromEntityId(),
            toEntityId: $loan->getToEntityId(),
            loanType: $loan->getLoanType(),
            principalAmount: $loan->getPrincipalAmount(),
            interestRate: $loan->getInterestRate(),
            outstandingBalance: $loan->getOutstandingBalance(),
            startDate: $loan->getStartDate(),
            maturityDate: $loan->getMaturityDate(),
            accruedInterest: $accruedInterest,
            paymentSchedule: $loan->getPaymentSchedule(),
            referenceNumber: $loan->getReferenceNumber(),
            notes: $loan->getNotes(),
            createdAt: $loan->getCreatedAt(),
            updatedAt: $date
        );

        $this->persist->save($updated);

        return $updated;
    }

    private function calculateAccruedInterest(
        Money $principal,
        float $interestRate,
        DateTimeImmutable $startDate,
        DateTimeImmutable $asOfDate
    ): Money {
        return InterestCalculator::calculateSimpleInterest(
            $principal,
            $interestRate,
            $startDate,
            $asOfDate
        );
    }

    private function addPaymentToSchedule(array $schedule, Money $amount, DateTimeImmutable $date): array
    {
        $schedule[] = [
            'date' => $date->format('Y-m-d'),
            'amount' => $amount->getAmount(),
            'currency' => $amount->getCurrency(),
        ];

        return $schedule;
    }

    private function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-ICL-' . $uuid;
    }
}
