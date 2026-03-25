<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

/**
 * @param list<ApprovalInstanceReadModel> $items
 */
final readonly class ApprovalInstancePageReadModel
{
    /**
     * @param list<ApprovalInstanceReadModel> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $lastPage,
    ) {
    }
}
