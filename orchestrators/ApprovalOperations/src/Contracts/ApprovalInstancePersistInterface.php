<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Contracts;

use Nexus\ApprovalOperations\DTOs\ApprovalInstanceReadModel;

interface ApprovalInstancePersistInterface
{
    public function save(ApprovalInstanceReadModel $instance): void;
}
