<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Exceptions;

use Nexus\QuotationIntelligence\Contracts\ComparisonReadinessResultInterface;

final class ComparisonNotReadyException extends QuotationIntelligenceException
{
    private ComparisonReadinessResultInterface $result;

    public static function fromResult(ComparisonReadinessResultInterface $result): self
    {
        $messages = array_map(
            static fn(array $b): string => $b['message'],
            $result->getBlockers()
        );

        $exception = new self(
            sprintf('Comparison run blocked: %s', implode('; ', $messages))
        );
        $exception->result = $result;

        return $exception;
    }

    public function getReadinessResult(): ComparisonReadinessResultInterface
    {
        return $this->result;
    }
}
