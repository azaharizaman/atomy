<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\QuoteApprovalDecision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteApprovalDecision>
 */
final class QuoteApprovalDecisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteApprovalDecision::class);
    }
}

