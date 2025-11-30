<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\SequenceInterface;

/**
 * Service for automatic pattern migration when sequences are exhausted.
 */
final readonly class PatternMigrationService
{
    public function __construct(
        private PatternVersionManager $versionManager,
    ) {}

    /**
     * Generate a new pattern by extending the current one.
     *
     * Example: "INV-{COUNTER:4}" → "INV-A-{COUNTER:4}"
     */
    public function generateMigrationPattern(string $currentPattern, string $suffix = 'A'): string
    {
        // Find the counter variable
        if (preg_match('/(\{COUNTER(?::\d+)?\})/', $currentPattern, $matches)) {
            $counterVar = $matches[1];
            return str_replace($counterVar, "-{$suffix}-{$counterVar}", $currentPattern);
        }

        // If no counter found, append suffix before the pattern end
        return rtrim($currentPattern, '}-') . "-{$suffix}";
    }

    /**
     * Apply automatic migration to a sequence.
     */
    public function migrate(
        SequenceInterface $sequence,
        string $migrationStrategy = 'add_suffix',
        ?\DateTimeInterface $effectiveFrom = null
    ): string {
        $currentPattern = $sequence->getPattern();
        $effectiveFrom ??= new \DateTimeImmutable();

        $newPattern = match ($migrationStrategy) {
            'add_suffix' => $this->generateMigrationPattern($currentPattern),
            'extend_padding' => $this->extendPadding($currentPattern),
            default => throw new \InvalidArgumentException("Unknown migration strategy: {$migrationStrategy}"),
        };

        $this->versionManager->createVersion($sequence, $newPattern, $effectiveFrom);

        return $newPattern;
    }

    /**
     * Extend padding in the counter variable.
     *
     * Example: "INV-{COUNTER:4}" → "INV-{COUNTER:5}"
     */
    private function extendPadding(string $pattern): string
    {
        return preg_replace_callback(
            '/\{COUNTER:(\d+)\}/',
            fn($matches) => '{COUNTER:' . ((int) $matches[1] + 1) . '}',
            $pattern
        );
    }
}
