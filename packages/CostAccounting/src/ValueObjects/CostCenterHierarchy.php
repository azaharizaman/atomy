<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\ValueObjects;

use Nexus\CostAccounting\Entities\CostCenter;

/**
 * Cost Center Hierarchy Value Object
 * 
 * Represents the tree structure of cost center hierarchy.
 */
final readonly class CostCenterHierarchy
{
    /** @var array<CostCenter> */
    private array $costCenters;
    
    /** @var array<string, string> */
    private array $parentMap;
    
    /** @var array<string, array<string>> */
    private array $childrenMap;

    /**
     * @param array<CostCenter> $costCenters
     * @param array<string, string> $parentMap Cost center ID to parent ID
     */
    public function __construct(
        array $costCenters,
        array $parentMap = []
    ) {
        $this->costCenters = $costCenters;
        $this->parentMap = $parentMap;
        $this->childrenMap = $this->buildChildrenMap($costCenters, $parentMap);
    }

    public function getCostCenters(): array
    {
        return $this->costCenters;
    }

    public function getRootCostCenters(): array
    {
        return array_filter(
            $this->costCenters,
            fn(CostCenter $cc) => $cc->isRoot()
        );
    }

    public function getChildren(string $costCenterId): array
    {
        $childrenIds = $this->childrenMap[$costCenterId] ?? [];
        
        return array_filter(
            $this->costCenters,
            fn(CostCenter $cc) => in_array($cc->getId(), $childrenIds, true)
        );
    }

    public function getParent(string $costCenterId): ?string
    {
        return $this->parentMap[$costCenterId] ?? null;
    }

    public function hasChildren(string $costCenterId): bool
    {
        return !empty($this->childrenMap[$costCenterId] ?? []);
    }

    public function getDepth(string $costCenterId): int
    {
        $depth = 0;
        $currentId = $costCenterId;
        
        while (isset($this->parentMap[$currentId])) {
            $depth++;
            $currentId = $this->parentMap[$currentId];
        }
        
        return $depth;
    }

    public function getPath(string $costCenterId): array
    {
        $path = [$costCenterId];
        $currentId = $costCenterId;
        
        while (isset($this->parentMap[$currentId])) {
            $currentId = $this->parentMap[$currentId];
            $path[] = $currentId;
        }
        
        return array_reverse($path);
    }

    /**
     * @return array<string, array<string>>
     */
    private function buildChildrenMap(
        array $costCenters,
        array $parentMap
    ): array {
        $childrenMap = [];
        
        foreach ($parentMap as $childId => $parentId) {
            if (!isset($childrenMap[$parentId])) {
                $childrenMap[$parentId] = [];
            }
            $childrenMap[$parentId][] = $childId;
        }
        
        return $childrenMap;
    }
}
