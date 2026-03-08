<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Exceptions;

use Nexus\QuotationIntelligence\Contracts\ComparisonReadinessResultInterface;

/**
 * Exception thrown when a comparison run is attempted but requirements are not met.
 */
class ComparisonNotReadyException extends \RuntimeException
{
    private ComparisonReadinessResultInterface $result;

    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fromResult(ComparisonReadinessResultInterface $result): self
    {
        $errors = $result->getBlockers();
        if ($errors === [] && $result->isPreviewOnly()) {
            $errors = $result->getWarnings();
        }

        $blockerMessages = array_map(static fn(array $b) => $b['message'] ?? 'Unknown error', $errors);
        $message = "Comparison is not ready: " . implode(', ', $blockerMessages);
        $exception = new self($message);
        $exception->result = $result;

        return $exception;
    }

    public function getReadinessResult(): ComparisonReadinessResultInterface
    {
        return $this->result;
    }
}
