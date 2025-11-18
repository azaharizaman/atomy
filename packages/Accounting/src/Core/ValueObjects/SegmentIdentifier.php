<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\ValueObjects;

/**
 * Segment identifier for multi-dimensional reporting.
 *
 * Enables reporting by department, division, project, etc.
 */
final readonly class SegmentIdentifier
{
    /**
     * @param array<string, string> $dimensions
     */
    public function __construct(
        private string $type,
        private array $dimensions,
        private string $label
    ) {
        if (empty($this->dimensions)) {
            throw new \InvalidArgumentException('At least one dimension must be specified');
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, string>
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get a specific dimension value.
     */
    public function getDimension(string $key): ?string
    {
        return $this->dimensions[$key] ?? null;
    }

    /**
     * Check if a dimension exists.
     */
    public function hasDimension(string $key): bool
    {
        return isset($this->dimensions[$key]);
    }

    /**
     * Get the number of dimensions.
     */
    public function getDimensionCount(): int
    {
        return count($this->dimensions);
    }

    /**
     * Create a segment for a department.
     */
    public static function forDepartment(string $departmentId, string $departmentName): self
    {
        return new self('department', ['department_id' => $departmentId], $departmentName);
    }

    /**
     * Create a segment for a division.
     */
    public static function forDivision(string $divisionId, string $divisionName): self
    {
        return new self('division', ['division_id' => $divisionId], $divisionName);
    }

    /**
     * Create a segment for a project.
     */
    public static function forProject(string $projectId, string $projectName): self
    {
        return new self('project', ['project_id' => $projectId], $projectName);
    }

    /**
     * Create a multi-dimensional segment.
     *
     * @param array<string, string> $dimensions
     */
    public static function multiDimensional(array $dimensions, string $label): self
    {
        return new self('multi', $dimensions, $label);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'dimensions' => $this->dimensions,
            'label' => $this->label,
        ];
    }

    /**
     * Generate a unique key for this segment.
     */
    public function toKey(): string
    {
        ksort($this->dimensions);
        return $this->type . ':' . http_build_query($this->dimensions);
    }
}
