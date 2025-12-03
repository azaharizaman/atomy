<?php

declare(strict_types=1);

namespace Nexus\TrainingManagement\Contracts;

use Nexus\TrainingManagement\Entities\Trainer;

interface TrainerRepositoryInterface
{
    public function findById(string $id): ?Trainer;
    public function findByCourseId(string $courseId): array;
    public function save(Trainer $trainer): void;
}
