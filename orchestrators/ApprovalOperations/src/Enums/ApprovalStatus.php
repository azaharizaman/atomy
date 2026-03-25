<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Enums;

enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
