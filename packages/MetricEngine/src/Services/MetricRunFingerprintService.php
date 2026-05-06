<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricRunFingerprint;
use Nexus\MetricEngine\ValueObjects\MetricSeries;

class MetricRunFingerprintService
{
    public function __construct(
        private readonly FormulaDefinitionSerializerService $serializer,
        private readonly FormulaGraphService $graphService = new FormulaGraphService()
    ) {}

    /**
     * @param array<string, MetricInput|MetricSeries> $inputs
     * @param array<string, mixed> $metadata
     */
    public function fingerprint(FormulaCatalog $catalog, array $inputs, array $metadata = []): MetricRunFingerprint
    {
        $payload = [
            'formulas' => $this->normalizeFormulas($catalog),
            'dependency_graph' => $this->normalizeDependencyGraph($catalog),
            'inputs' => $this->normalizeInputs($inputs),
            'metadata' => $this->canonicalize($metadata),
        ];

        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        return new MetricRunFingerprint('sha256', hash('sha256', $json));
    }

    /** @return array<string, mixed> */
    private function normalizeFormulas(FormulaCatalog $catalog): array
    {
        $formulas = $catalog->all();
        ksort($formulas);

        return array_map(
            fn ($formula) => $this->canonicalize($this->serializer->toArray($formula)),
            $formulas
        );
    }

    /** @return array<string, list<string>> */
    private function normalizeDependencyGraph(FormulaCatalog $catalog): array
    {
        $graph = $this->graphService->build($catalog);
        $dependencies = [];

        foreach ($catalog->all() as $identifier => $_formula) {
            $formulaDependencies = $graph->dependenciesFor($identifier);
            sort($formulaDependencies);
            $dependencies[$identifier] = $formulaDependencies;
        }

        ksort($dependencies);

        return $dependencies;
    }

    /**
     * @param array<string, MetricInput|MetricSeries> $inputs
     * @return array<string, mixed>
     */
    private function normalizeInputs(array $inputs): array
    {
        ksort($inputs);
        $normalized = [];

        foreach ($inputs as $key => $input) {
            if ($input instanceof MetricInput) {
                $normalized[$key] = $this->canonicalize([
                    'name' => $input->name,
                    'value' => $input->value,
                    'unit' => $input->unit,
                ]);
                continue;
            }

            if ($input instanceof MetricSeries) {
                $normalized[$key] = $this->canonicalize([
                    'name' => $input->name,
                    'unit' => $input->unit,
                    'points' => array_map(
                        static fn ($point) => [
                            'period_key' => $point->periodKey,
                            'value' => $point->value,
                            'metadata' => $point->metadata,
                        ],
                        $input->points
                    ),
                ]);
                continue;
            }

            throw new \InvalidArgumentException('Input must be MetricInput or MetricSeries, got ' . get_class($input));
        }

        return $normalized;
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $canonical = array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);

        if (! array_is_list($canonical)) {
            ksort($canonical);
        }

        return $canonical;
    }
}
