<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Policies;

final readonly class DocumentSubmissionPolicy
{
    public function getRequiredDocuments(): array
    {
        return ['id_card', 'resume', 'certificates'];
    }
}
