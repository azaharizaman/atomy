<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use DateTimeInterface;
use Nexus\Sequencing\Exceptions\InvalidPatternException;
use Nexus\Sequencing\ValueObjects\PatternVariable;

/**
 * Service for parsing and generating numbers from patterns.
 */
final readonly class PatternParser
{
    /**
     * Parse a pattern and generate a number.
     *
     * @param string $pattern Pattern template (e.g., "INV-{YEAR}-{COUNTER:5}")
     * @param int $counterValue Current counter value
     * @param array<string, string|int> $contextVariables Custom variables
     * @param DateTimeInterface|null $date Date for date/time variables (defaults to now)
     * @return string Generated number
     * @throws InvalidPatternException
     */
    public function parse(
        string $pattern,
        int $counterValue,
        array $contextVariables = [],
        ?DateTimeInterface $date = null
    ): string {
        $date ??= new \DateTimeImmutable();
        $result = $pattern;

        // Extract all variables from pattern
        preg_match_all('/\{([^}]+)\}/', $pattern, $matches);
        $variables = $matches[1] ?? [];

        foreach ($variables as $variableStr) {
            $variable = PatternVariable::parse($variableStr);
            $value = $this->resolveVariable($variable, $counterValue, $contextVariables, $date);
            $formattedValue = $variable->format($value);
            $result = str_replace($variable->toPattern(), $formattedValue, $result);
        }

        return $result;
    }

    /**
     * Validate pattern syntax.
     *
     * @throws InvalidPatternException
     */
    public function validateSyntax(string $pattern): void
    {
        // Check for balanced braces
        if (substr_count($pattern, '{') !== substr_count($pattern, '}')) {
            throw InvalidPatternException::invalidSyntax($pattern, 'Unbalanced braces');
        }

        // Extract variables and validate each one
        preg_match_all('/\{([^}]+)\}/', $pattern, $matches);
        $variables = $matches[1] ?? [];

        foreach ($variables as $variableStr) {
            $variable = PatternVariable::parse($variableStr);

            if (!$this->isValidVariable($variable)) {
                throw InvalidPatternException::unknownVariable($variable->name);
            }

            if ($variable->padding !== null && $variable->padding <= 0) {
                throw InvalidPatternException::invalidPadding($variable->name, $variable->padding);
            }
        }
    }

    /**
     * Extract all variables from a pattern.
     *
     * @return PatternVariable[]
     */
    public function extractVariables(string $pattern): array
    {
        preg_match_all('/\{([^}]+)\}/', $pattern, $matches);
        $variableStrings = $matches[1] ?? [];

        return array_map(
            fn(string $var) => PatternVariable::parse($var),
            $variableStrings
        );
    }

    /**
     * Generate a regex pattern to validate generated numbers.
     */
    public function generateValidationRegex(string $pattern): string
    {
        $regex = preg_quote($pattern, '/');

        // Replace variables with appropriate regex patterns
        $replacements = [
            '\{YEAR(?::(\d+))?\}' => '(\d{4})',
            '\{YY(?::(\d+))?\}' => '(\d{2})',
            '\{MONTH(?::(\d+))?\}' => '(0[1-9]|1[0-2])',
            '\{DAY(?::(\d+))?\}' => '(0[1-9]|[12][0-9]|3[01])',
            '\{COUNTER(?::(\d+))?\}' => '(\d+)',
        ];

        foreach ($replacements as $varPattern => $replacement) {
            $regex = preg_replace('/' . $varPattern . '/', $replacement, $regex);
        }

        // Handle custom variables (alphanumeric)
        $regex = preg_replace('/\\\{([A-Z_]+)(?::(\d+))?\\\}/', '([A-Z0-9]+)', $regex);

        return '/^' . $regex . '$/';
    }

    /**
     * Resolve a variable to its value.
     *
     * @param array<string, string|int> $contextVariables
     */
    private function resolveVariable(
        PatternVariable $variable,
        int $counterValue,
        array $contextVariables,
        DateTimeInterface $date
    ): string|int {
        $name = strtoupper($variable->name);

        // Date/time variables
        return match ($name) {
            'YEAR' => $date->format('Y'),
            'YY' => $date->format('y'),
            'MONTH' => $date->format('m'),
            'DAY' => $date->format('d'),
            'COUNTER' => $counterValue,
            default => $contextVariables[$name] ?? $contextVariables[strtolower($name)] ?? 
                throw InvalidPatternException::unknownVariable($variable->name),
        };
    }

    /**
     * Check if a variable name is valid.
     */
    private function isValidVariable(PatternVariable $variable): bool
    {
        $knownVariables = ['YEAR', 'YY', 'MONTH', 'DAY', 'COUNTER'];
        $name = strtoupper($variable->name);

        // Known built-in variables are always valid
        if (in_array($name, $knownVariables, true)) {
            return true;
        }

        // Custom variables must be uppercase alphanumeric with underscores
        return preg_match('/^[A-Z][A-Z0-9_]*$/', $name) === 1;
    }
}
