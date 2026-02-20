<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Contracts;

use Nexus\PerformanceReview\Entities\Kpi;

interface KpiRepositoryInterface
{
    public function findById(string $id): ?Kpi;
    public function findByAppraisalId(string $appraisalId): array;
    public function save(Kpi $kpi): void;
}
