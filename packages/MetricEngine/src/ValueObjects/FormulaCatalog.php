<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Exceptions\DuplicateFormulaException;
use Nexus\MetricEngine\Exceptions\MissingInputException;

final readonly class FormulaCatalog
{
    /** @var array<string, FormulaInterface> */
    private array $formulas;

    /** @param list<FormulaInterface> $formulas */
    public function __construct(array $formulas)
    {
        $indexed = [];

        foreach ($formulas as $formula) {
            $identifier = $formula->identifier();

            if (isset($indexed[$identifier])) {
                throw new DuplicateFormulaException($identifier);
            }

            $indexed[$identifier] = $formula;
        }

        $this->formulas = $indexed;
    }

    public function get(string $identifier): FormulaInterface
    {
        if (! isset($this->formulas[$identifier])) {
            throw new MissingInputException($identifier);
        }

        return $this->formulas[$identifier];
    }

    public function has(string $identifier): bool
    {
        return isset($this->formulas[$identifier]);
    }

    /** @return array<string, FormulaInterface> */
    public function all(): array
    {
        return $this->formulas;
    }
}
