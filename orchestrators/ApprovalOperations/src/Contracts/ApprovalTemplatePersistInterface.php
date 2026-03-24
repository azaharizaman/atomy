<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Contracts;

use Nexus\ApprovalOperations\DTOs\ApprovalTemplateReadModel;

interface ApprovalTemplatePersistInterface
{
    public function save(ApprovalTemplateReadModel $template): void;
}
