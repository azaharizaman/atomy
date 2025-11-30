<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;

/**
 * Service for validating sequence numbers against patterns.
 */
final readonly class SequenceValidationService
{
    public function __construct(
        private SequenceRepositoryInterface $sequenceRepository,
        private PatternParser $patternParser,
    ) {}

    /**
     * Validate if a given number matches a sequence pattern.
     */
    public function validateNumber(
        string $sequenceName,
        string $number,
        ?string $scopeIdentifier = null
    ): bool {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        $pattern = $sequence->getPattern();

        $regex = $this->patternParser->generateValidationRegex($pattern);

        return preg_match($regex, $number) === 1;
    }

    /**
     * Validate pattern syntax.
     *
     * @throws \Nexus\Sequencing\Exceptions\InvalidPatternException
     */
    public function validatePatternSyntax(string $pattern): void
    {
        $this->patternParser->validateSyntax($pattern);
    }

    /**
     * Detect if two patterns could generate colliding numbers.
     */
    public function detectCollisions(string $pattern1, string $pattern2): bool
    {
        // Extract static parts (non-variable parts) from both patterns
        $static1 = preg_replace('/\{[^}]+\}/', '', $pattern1);
        $static2 = preg_replace('/\{[^}]+\}/', '', $pattern2);

        // If static parts are different, no collision possible
        if ($static1 !== $static2) {
            return false;
        }

        // Get variable positions and types
        $vars1 = $this->patternParser->extractVariables($pattern1);
        $vars2 = $this->patternParser->extractVariables($pattern2);

        // If variable count differs, check if they could still collide
        if (count($vars1) !== count($vars2)) {
            return false;
        }

        // Check if variables are in same positions with same types
        foreach ($vars1 as $i => $var1) {
            $var2 = $vars2[$i] ?? null;
            if ($var2 === null || $var1->name !== $var2->name) {
                return false;
            }
        }

        // Patterns are structurally identical and could collide
        return true;
    }
}
