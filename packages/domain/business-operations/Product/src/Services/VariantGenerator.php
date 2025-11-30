<?php

declare(strict_types=1);

namespace Nexus\Product\Services;

use Nexus\Product\Contracts\AttributeRepositoryInterface;
use Nexus\Product\Contracts\ProductTemplateInterface;
use Nexus\Product\Contracts\ProductTemplateRepositoryInterface;
use Nexus\Product\Contracts\ProductVariantInterface;
use Nexus\Product\Exceptions\ProductTemplateNotFoundException;
use Nexus\Product\Exceptions\VariantExplosionException;
use Nexus\Setting\Services\SettingsManager;
use Psr\Log\LoggerInterface;

/**
 * Variant Generator Service
 *
 * Generates all possible product variants from attribute combinations.
 * Includes safeguards against variant explosion.
 */
class VariantGenerator
{
    private const DEFAULT_MAX_VARIANTS = 1000;
    private const DEFAULT_MAX_ATTRIBUTES = 10;

    public function __construct(
        private readonly ProductTemplateRepositoryInterface $templateRepository,
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly SettingsManager $settings,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Calculate number of variants that would be generated
     *
     * @param array<string, array<string>> $attributeValues
     * @return int
     */
    public function calculateVariantCount(array $attributeValues): int
    {
        if (empty($attributeValues)) {
            return 0;
        }

        $count = 1;
        foreach ($attributeValues as $values) {
            $count *= count($values);
        }

        return $count;
    }

    /**
     * Generate all variant combinations from attributes
     *
     * @param string $templateId
     * @param array<string, array<string>> $attributeValues e.g., ['COLOR' => ['Red', 'Blue'], 'SIZE' => ['S', 'M', 'L']]
     * @return array<array<string, string>> Array of variant attribute combinations
     * @throws ProductTemplateNotFoundException
     * @throws VariantExplosionException
     */
    public function generateCombinations(string $templateId, array $attributeValues): array
    {
        $template = $this->templateRepository->findById($templateId);
        if ($template === null) {
            throw ProductTemplateNotFoundException::forId($templateId);
        }

        // Validate attribute count
        $attributeCount = count($attributeValues);
        $maxAttributes = $this->settings->getInt('product.max_attributes_per_template', self::DEFAULT_MAX_ATTRIBUTES);
        
        if ($attributeCount > $maxAttributes) {
            throw VariantExplosionException::tooManyAttributes($attributeCount, $maxAttributes);
        }

        // Calculate total variant count
        $variantCount = $this->calculateVariantCount($attributeValues);
        $maxVariants = $this->settings->getInt('product.max_variants_per_template', self::DEFAULT_MAX_VARIANTS);

        if ($variantCount > $maxVariants) {
            throw VariantExplosionException::exceededLimit($variantCount, $maxVariants);
        }

        $this->logger->info('Generating product variants', [
            'template_id' => $templateId,
            'template_code' => $template->getCode(),
            'attribute_count' => $attributeCount,
            'variant_count' => $variantCount,
        ]);

        return $this->cartesianProduct($attributeValues);
    }

    /**
     * Generate Cartesian product of attribute values
     *
     * @param array<string, array<string>> $attributeValues
     * @return array<array<string, string>>
     */
    private function cartesianProduct(array $attributeValues): array
    {
        if (empty($attributeValues)) {
            return [];
        }

        // Extract attribute codes and their values
        $attributeCodes = array_keys($attributeValues);
        $valueArrays = array_values($attributeValues);

        // Generate all combinations
        $combinations = [[]];
        
        foreach ($valueArrays as $index => $values) {
            $append = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $append[] = array_merge($combination, [$attributeCodes[$index] => $value]);
                }
            }
            $combinations = $append;
        }

        return $combinations;
    }

    /**
     * Generate variant name from template and attributes
     *
     * @param ProductTemplateInterface $template
     * @param array<string, string> $attributeValues
     * @return string
     */
    public function generateVariantName(ProductTemplateInterface $template, array $attributeValues): string
    {
        if (empty($attributeValues)) {
            return $template->getName();
        }

        $attributeString = implode(', ', array_values($attributeValues));
        return "{$template->getName()} ({$attributeString})";
    }

    /**
     * Validate attribute combinations exist in repository
     *
     * @param string $tenantId
     * @param array<string> $attributeCodes
     * @return bool
     */
    public function validateAttributes(string $tenantId, array $attributeCodes): bool
    {
        $attributes = $this->attributeRepository->getByCodes($tenantId, $attributeCodes);
        return count($attributes) === count($attributeCodes);
    }
}
