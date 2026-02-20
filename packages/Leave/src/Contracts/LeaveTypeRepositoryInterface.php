<?php

declare(strict_types=1);

namespace Nexus\Leave\Contracts;

interface LeaveTypeRepositoryInterface
{
    public function findById(string $id): ?object;
    
    public function findAll(): array;
    
    public function save(object $leaveType): string;
}
