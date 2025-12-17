<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\Enums\RequestType;

/**
 * Contract for request type-specific handlers.
 */
interface RequestHandlerInterface
{
    /**
     * Check if this handler supports the given request type.
     */
    public function supports(RequestType $type): bool;

    /**
     * Execute the data subject request.
     *
     * @param DataSubjectRequest $request The request to execute
     * @return array<string, mixed> Execution result with details
     */
    public function execute(DataSubjectRequest $request): array;

    /**
     * Validate that the request can be processed.
     *
     * @param DataSubjectRequest $request The request to validate
     * @return array<string> Validation errors (empty if valid)
     */
    public function validate(DataSubjectRequest $request): array;

    /**
     * Get estimated processing days for this request type.
     */
    public function getEstimatedProcessingDays(): int;
}
