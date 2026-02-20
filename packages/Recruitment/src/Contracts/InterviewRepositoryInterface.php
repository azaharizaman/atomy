<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Contracts;

use Nexus\Recruitment\Entities\Interview;

interface InterviewRepositoryInterface
{
    public function findById(string $id): ?Interview;
    public function findByApplicantId(string $applicantId): array;
    public function save(Interview $interview): void;
}
