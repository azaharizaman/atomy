<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * Task action enum.
 *
 * Represents actions that can be taken on a task.
 */
enum TaskAction: string
{
    case APPROVE = 'approve';
    case REJECT = 'reject';
    case REQUEST_CHANGES = 'request_changes';
    case DELEGATE = 'delegate';
    case CANCEL = 'cancel';
}
