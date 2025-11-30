<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * Approval strategy enum.
 *
 * Defines how multi-approver tasks are resolved.
 */
enum ApprovalStrategy: string
{
    /** All assignees must approve */
    case UNISON = 'unison';
    
    /** More than 50% must approve */
    case MAJORITY = 'majority';
    
    /** Configurable threshold (e.g., 3 of 5) */
    case QUORUM = 'quorum';
    
    /** Votes have different weights */
    case WEIGHTED = 'weighted';
    
    /** First approval wins */
    case FIRST = 'first';
}
