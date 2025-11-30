<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Manufacturing\Contracts\BomRepositoryInterface;
use Nexus\Manufacturing\Contracts\BomInterface;
use Nexus\Manufacturing\Exceptions\BomNotFoundException;
use Nexus\Manufacturing\Exceptions\CircularBomException;
use Nexus\Manufacturing\Exceptions\InvalidBomVersionException;
use Nexus\Manufacturing\ValueObjects\BomLine;

/**
 * BOM Manager implementation.
 *
 * Manages Bill of Materials lifecycle, versioning, and explosion.
 */
final readonly class BomManager implements BomManagerInterface
{
    public function __construct(
        private BomRepositoryInterface $repository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        string $productId,
        string $version,
        string $type,
        array $lines = [],
        ?\DateTimeImmutable $effectiveFrom = null,
        ?\DateTimeImmutable $effectiveTo = null
    ): BomInterface {
        // Validate circular dependencies
        $productIds = array_map(fn (BomLine $line) => $line->productId, $lines);
        $this->validateNoCircularDependency($productId, $productIds);

        return $this->repository->create([
            'productId' => $productId,
            'type' => $type,
            'version' => $version,
            'lines' => array_map(fn (BomLine $line) => $line->toArray(), $lines),
            'effectiveFrom' => $effectiveFrom?->format('Y-m-d'),
            'effectiveTo' => $effectiveTo?->format('Y-m-d'),
            'status' => 'draft',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getById(string $id): BomInterface
    {
        $bom = $this->repository->findById($id);

        if ($bom === null) {
            throw BomNotFoundException::withId($id);
        }

        return $bom;
    }

    /**
     * {@inheritdoc}
     */
    public function findByProductId(string $productId): BomInterface
    {
        return $this->getEffective($productId);
    }

    /**
     * {@inheritdoc}
     */
    public function getEffective(string $productId, ?\DateTimeImmutable $asOfDate = null): BomInterface
    {
        $effectiveDate = $asOfDate ?? new \DateTimeImmutable();
        $bom = $this->repository->findByProductId($productId, $effectiveDate);

        if ($bom === null) {
            throw BomNotFoundException::noEffectiveBom($productId, $effectiveDate->format('Y-m-d'));
        }

        return $bom;
    }

    /**
     * {@inheritdoc}
     */
    public function createVersion(
        string $sourceBomId,
        string $newVersion,
        ?\DateTimeImmutable $effectiveFrom = null
    ): BomInterface {
        $sourceBom = $this->getById($sourceBomId);
        $productId = $sourceBom->getProductId();

        // Check if version already exists
        $existingVersions = $this->repository->findAllVersions($productId);
        foreach ($existingVersions as $existing) {
            if ($existing->getVersion() === $newVersion) {
                throw InvalidBomVersionException::versionExists($productId, $newVersion);
            }
        }

        return $this->repository->create([
            'productId' => $productId,
            'type' => $sourceBom->getType(),
            'version' => $newVersion,
            'lines' => array_map(fn (BomLine $line) => $line->toArray(), $sourceBom->getLines()),
            'effectiveFrom' => $effectiveFrom?->format('Y-m-d'),
            'status' => 'draft',
            'previousVersionId' => $sourceBomId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function release(string $bomId): void
    {
        $bom = $this->getById($bomId);

        if (count($bom->getLines()) === 0) {
            throw InvalidBomVersionException::cannotRelease($bomId, 'BOM has no lines');
        }

        $this->repository->update($bomId, [
            'status' => 'released',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function obsolete(string $bomId): void
    {
        $this->getById($bomId);

        $this->repository->update($bomId, [
            'status' => 'obsolete',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function addLine(string $bomId, BomLine $line): void
    {
        $bom = $this->getById($bomId);

        if ($bom->getStatus() === 'released') {
            throw InvalidBomVersionException::cannotModify($bomId, 'released');
        }

        // Check for circular dependency with new component
        $this->validateNoCircularDependency($bom->getProductId(), [$line->productId]);

        $lines = $bom->getLines();
        $lines[] = $line;

        $this->repository->update($bomId, [
            'lines' => array_map(fn (BomLine $l) => $l->toArray(), $lines),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function updateLine(string $bomId, int $lineNumber, BomLine $line): void
    {
        $bom = $this->getById($bomId);

        if ($bom->getStatus() === 'released') {
            throw InvalidBomVersionException::cannotModify($bomId, 'released');
        }

        // Check for circular dependency with updated component
        $this->validateNoCircularDependency($bom->getProductId(), [$line->productId]);

        $lines = $bom->getLines();
        $updated = false;
        
        foreach ($lines as $index => $existingLine) {
            if ($existingLine->lineNumber === $lineNumber) {
                $lines[$index] = $line;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            throw new \InvalidArgumentException("Line number {$lineNumber} not found in BOM {$bomId}");
        }

        $this->repository->update($bomId, [
            'lines' => array_map(fn (BomLine $l) => $l->toArray(), $lines),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeLine(string $bomId, int $lineNumber): void
    {
        $bom = $this->getById($bomId);

        if ($bom->getStatus() === 'released') {
            throw InvalidBomVersionException::cannotModify($bomId, 'released');
        }

        $lines = array_filter(
            $bom->getLines(),
            fn (BomLine $line) => $line->lineNumber !== $lineNumber
        );

        $this->repository->update($bomId, [
            'lines' => array_map(fn (BomLine $l) => $l->toArray(), array_values($lines)),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function explode(string $bomId, float $parentQuantity = 1.0): array
    {
        $bom = $this->getById($bomId);

        return $this->explodeBom($bom, $parentQuantity, 0, 99, [$bom->getProductId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function whereUsed(string $componentProductId): array
    {
        return $this->repository->findWhereUsed($componentProductId);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $bomId): array
    {
        $errors = [];
        $bom = $this->getById($bomId);
        
        // Check for empty BOM
        if (count($bom->getLines()) === 0) {
            $errors[] = 'BOM has no components';
        }

        // Check for circular references
        try {
            $this->explode($bomId);
        } catch (CircularBomException $e) {
            $errors[] = 'Circular reference detected: ' . $e->getMessage();
        }

        // Check for duplicate line numbers
        $lineNumbers = array_map(fn (BomLine $l) => $l->lineNumber, $bom->getLines());
        if (count($lineNumbers) !== count(array_unique($lineNumbers))) {
            $errors[] = 'Duplicate line numbers found';
        }

        // Check for zero or negative quantities
        foreach ($bom->getLines() as $line) {
            if ($line->quantity <= 0) {
                $errors[] = "Line {$line->lineNumber}: quantity must be positive";
            }
        }

        return $errors;
    }

    /**
     * Recursively explode BOM.
     *
     * @param array<string> $visited Products already visited (for circular detection)
     * @return array<array{productId: string, quantity: float, level: int, uomCode: string}>
     */
    private function explodeBom(
        BomInterface $bom,
        float $quantity,
        int $currentLevel,
        int $maxLevels,
        array $visited
    ): array {
        $result = [];

        foreach ($bom->getLines() as $line) {
            $requiredQuantity = $line->getQuantityWithScrap() * $quantity;

            $result[] = [
                'productId' => $line->productId,
                'quantity' => $requiredQuantity,
                'level' => $currentLevel + 1,
                'uomCode' => $line->uomCode,
            ];

            // Check for circular dependency
            if (in_array($line->productId, $visited, true)) {
                throw CircularBomException::withPath([...$visited, $line->productId]);
            }

            // Recursively explode if there's a BOM and not at max level
            if ($currentLevel < $maxLevels - 1) {
                $childBom = $this->repository->findByProductId($line->productId);
                if ($childBom !== null) {
                    $childResults = $this->explodeBom(
                        $childBom,
                        $requiredQuantity,
                        $currentLevel + 1,
                        $maxLevels,
                        [...$visited, $line->productId]
                    );
                    $result = [...$result, ...$childResults];
                }
            }
        }

        return $result;
    }

    /**
     * Validate no circular dependencies exist.
     *
     * @param array<string> $componentProductIds
     */
    private function validateNoCircularDependency(string $parentProductId, array $componentProductIds): void
    {
        foreach ($componentProductIds as $componentId) {
            if ($componentId === $parentProductId) {
                throw CircularBomException::withPath([$parentProductId, $componentId]);
            }

            // Check if component has parent as a component (deep check)
            $componentBom = $this->repository->findByProductId($componentId);
            if ($componentBom !== null) {
                $this->checkCircularPath($parentProductId, $componentBom, [$parentProductId, $componentId]);
            }
        }
    }

    /**
     * Check for circular path recursively.
     *
     * @param array<string> $path
     */
    private function checkCircularPath(string $targetProductId, BomInterface $bom, array $path): void
    {
        foreach ($bom->getLines() as $line) {
            if ($line->productId === $targetProductId) {
                throw CircularBomException::withPath([...$path, $line->productId]);
            }

            $childBom = $this->repository->findByProductId($line->productId);
            if ($childBom !== null) {
                $this->checkCircularPath($targetProductId, $childBom, [...$path, $line->productId]);
            }
        }
    }
}
