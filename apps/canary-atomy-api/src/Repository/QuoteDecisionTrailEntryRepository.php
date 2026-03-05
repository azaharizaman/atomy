<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\QuoteComparisonRun;
use App\Entity\QuoteDecisionTrailEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteDecisionTrailEntry>
 */
final class QuoteDecisionTrailEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteDecisionTrailEntry::class);
    }

    public function findLastForRun(QuoteComparisonRun $run): ?QuoteDecisionTrailEntry
    {
        return $this->findOneBy(['comparisonRun' => $run], ['sequence' => 'DESC']);
    }
}

