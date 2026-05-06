<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;

final readonly class FormulaCatalogBuilderService
{
    public function __construct(
        private readonly FormulaDefinitionSerializerService $serializer
    ) {}

    /** @param list<FormulaInterface> $formulas */
    public function fromFormulas(array $formulas): FormulaCatalog
    {
        return new FormulaCatalog($formulas);
    }

    /** @param list<array<string, mixed>> $payloads */
    public function fromArrays(array $payloads): FormulaCatalog
    {
        $formulas = array_map(
            fn (array $payload) => $this->serializer->fromArray($payload),
            $payloads
        );

        return new FormulaCatalog($formulas);
    }
}
