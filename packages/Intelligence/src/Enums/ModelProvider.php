<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Enums;

/**
 * AI provider enumeration
 */
enum ModelProvider: string
{
    case OPENAI = 'openai';
    case ANTHROPIC = 'anthropic';
    case GEMINI = 'gemini';
    case AZURE_OPENAI = 'azure_openai';
    case RULE_BASED = 'rule_based';
}
