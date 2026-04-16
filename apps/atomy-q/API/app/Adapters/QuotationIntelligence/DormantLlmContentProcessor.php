<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;
use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;

final readonly class DormantLlmContentProcessor implements OrchestratorContentProcessorInterface
{
    private string $message;

    /**
     * @param array<string, mixed> $llmConfig
     */
    public function __construct(array $llmConfig)
    {
        $this->message = self::resolveFailureMessage($llmConfig);
    }

    public function analyze(string $storagePath): object
    {
        throw new QuotationIntelligenceException($this->message);
    }

    /**
     * @param array<string, mixed> $llmConfig
     */
    private static function resolveFailureMessage(array $llmConfig): string
    {
        foreach (['provider', 'model', 'base_url', 'api_key'] as $key) {
            if (!is_string($llmConfig[$key] ?? null) || trim((string) $llmConfig[$key]) === '') {
                return 'Quote intelligence LLM provider configuration is missing or incomplete.';
            }
        }

        return 'Quote intelligence LLM mode is configured but no production adapter is implemented yet.';
    }
}
