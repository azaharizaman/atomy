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
        private readonly FormulaDefinitionSerializerService $serializer = new FormulaDefinitionSerializerService()
    ) {}

    /**
     * @param array<string, MetricInput|MetricSeries> $inputs
     * @param array<string, mixed> $metadata
     */
    public function fingerprint(FormulaCatalog $catalog, array $inputs, array $metadata = []): MetricRunFingerprint
    {
        $payload = [
            'formulas' => array_map(
                fn ($formula) => $this->serializer->toArray($formula),
                $catalog->all()
            ),
            'inputs' => $this->normalizeInputs($inputs),
            'metadata' => $metadata,
        ];

        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        return new MetricRunFingerprint('sha256', hash('sha256', $json));
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
                $normalized[$key] = [
                    'name' => $input->name,
                    'value' => $input->value,
                    'unit' => $input->unit,
                ];
                continue;
            }

            $normalized[$key] = [
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
            ];
        }

        return $normalized;
    }
}
