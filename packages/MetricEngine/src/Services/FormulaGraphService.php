<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Contracts\FormulaGraphServiceInterface;
use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Exceptions\FormulaDependencyException;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaGraph;
use Nexus\MetricEngine\ValueObjects\FormulaReference;

class FormulaGraphService implements FormulaGraphServiceInterface
{
    public function build(FormulaCatalog $catalog): FormulaGraph
    {
        $dependencies = [];

        foreach ($catalog->all() as $identifier => $formula) {
            $dependencies[$identifier] = $this->extractDependencies($formula);

            foreach ($dependencies[$identifier] as $dependency) {
                if (! $catalog->has($dependency)) {
                    throw new FormulaDependencyException("Formula [{$identifier}] references missing formula [{$dependency}].");
                }
            }
        }

        return new FormulaGraph($this->topologicalSort($dependencies), $dependencies);
    }

    /** @return list<string> */
    private function extractDependencies(FormulaInterface $formula): array
    {
        return $this->collectReferences($formula->operands());
    }

    /**
     * @param list<mixed> $operands
     * @return list<string>
     */
    private function collectReferences(array $operands): array
    {
        $dependencies = [];

        foreach ($operands as $operand) {
            if ($operand instanceof FormulaReference) {
                $dependencies[] = $operand->identifier;
                continue;
            }

            if (is_array($operand)) {
                $nested = $this->collectReferences($operand);
                $dependencies = array_merge($dependencies, $nested);
            }
        }

        return array_values(array_unique($dependencies));
    }

    /**
     * @param array<string, list<string>> $dependencies
     * @return list<string>
     */
    private function topologicalSort(array $dependencies): array
    {
        $ordered = [];
        $temporary = [];
        $permanent = [];

        foreach (array_keys($dependencies) as $identifier) {
            $this->visit($identifier, $dependencies, $ordered, $temporary, $permanent);
        }

        return $ordered;
    }

    /**
     * @param array<string, list<string>> $dependencies
     * @param list<string> $ordered
     * @param array<string, bool> $temporary
     * @param array<string, bool> $permanent
     */
    private function visit(
        string $identifier,
        array $dependencies,
        array &$ordered,
        array &$temporary,
        array &$permanent
    ): void {
        if (isset($permanent[$identifier])) {
            return;
        }

        if (isset($temporary[$identifier])) {
            throw new FormulaDependencyException('Formula dependency cycle detected.');
        }

        $temporary[$identifier] = true;

        foreach ($dependencies[$identifier] ?? [] as $dependency) {
            $this->visit($dependency, $dependencies, $ordered, $temporary, $permanent);
        }

        unset($temporary[$identifier]);
        $permanent[$identifier] = true;
        $ordered[] = $identifier;
    }
}
