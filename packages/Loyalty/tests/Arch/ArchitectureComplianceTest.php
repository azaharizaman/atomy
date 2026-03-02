<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Arch;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;
use ReflectionType;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Ensures the package adheres to the mandated Three-Layer Architecture and Coding Standards.
 * Requirements: ARC-LOY-001 to 005, SEC-LOY-003, INT-LOY-001
 */
final class ArchitectureComplianceTest extends TestCase
{
    private const SRC_DIR = __DIR__ . '/../../src';

    /**
     * Requirement: ARC-LOY-004 - Every PHP file MUST start with `declare(strict_types=1);`
     */
    public function test_strict_types_declaration(): void
    {
        foreach ($this->getFiles() as $file) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('declare(strict_types=1);', $content, "Missing strict_types in $file");
        }
    }

    /**
     * Requirement: ARC-LOY-002 - All core services MUST be defined as `final readonly class`
     */
    public function test_services_are_final_readonly(): void
    {
        foreach ($this->getClassesInNamespace('Nexus\Loyalty\Services') as $className) {
            $reflection = new ReflectionClass($className);
            if ($reflection->isInterface() || $reflection->isTrait()) {
                continue;
            }
            $this->assertTrue($reflection->isFinal(), "Service $className must be final");
            $this->assertTrue($reflection->isReadOnly(), "Service $className must be readonly");
        }
    }

    /**
     * Requirement: ARC-LOY-003 - All Value Objects MUST use `readonly` properties for immutability
     */
    public function test_value_objects_are_readonly(): void
    {
        foreach ($this->getClassesInNamespace('Nexus\Loyalty\ValueObjects') as $className) {
            $reflection = new ReflectionClass($className);
            $this->assertTrue($reflection->isReadOnly(), "ValueObject $className must be readonly");
        }
    }

    /**
     * Requirement: ARC-LOY-002 - Entities MUST be defined as `final readonly class`
     */
    public function test_entities_are_readonly(): void
    {
        foreach ($this->getClassesInNamespace('Nexus\Loyalty\Entities') as $className) {
            $reflection = new ReflectionClass($className);
            $this->assertTrue($reflection->isFinal(), "Entity $className must be final");
            $this->assertTrue($reflection->isReadOnly(), "Entity $className must be readonly");
        }
    }

    /**
     * Requirement: ARC-LOY-001 - Package MUST be framework-agnostic (no Laravel/Symfony in core)
     */
    public function test_no_framework_dependencies(): void
    {
        $forbiddenPatterns = [
            '/use\s+Illuminate\\\\/',
            '/use\s+Symfony\\\\/',
            '/\\\\Illuminate\\\\/',
            '/\\\\Symfony\\\\/',
        ];

        foreach ($this->getFiles() as $file) {
            $content = file_get_contents($file);
            foreach ($forbiddenPatterns as $pattern) {
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $content,
                    "Forbidden framework dependency pattern matched in $file"
                );
            }
        }
    }

    /**
     * Requirement: ARC-LOY-005 - Use Constructor Injection with specific interfaces
     */
    public function test_dependency_injection_types(): void
    {
        foreach ($this->getClassesInNamespace('Nexus\Loyalty\Services') as $className) {
            $reflection = new ReflectionClass($className);
            $constructor = $reflection->getConstructor();
            if (!$constructor) {
                continue;
            }

            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                $this->assertNotNull($type, "Constructor parameter '{$param->getName()}' in $className must have a type hint");
                
                $namedTypes = $this->flattenNamedTypes($type);

                foreach ($namedTypes as $namedType) {
                    $typeName = $namedType->getName();
                    $this->assertNotEquals(
                        'object',
                        $typeName,
                        "Constructor parameter '{$param->getName()}' in $className cannot be generic 'object'"
                    );

                    // If it's a true injection (no default value) and looks like a class, verify it's an interface or enum
                    if (!$param->isDefaultValueAvailable() && !$namedType->isBuiltin()) {
                        $typeReflection = new ReflectionClass($typeName);
                        $this->assertTrue(
                            $typeReflection->isInterface() || $typeReflection->isEnum(),
                            "Injected dependency '{$param->getName()}' in $className should use an interface or enum, got class $typeName"
                        );
                    }
                }
            }
        }
    }

    /**
     * Recursively flatten ReflectionType into ReflectionNamedType array.
     * Handles Union, Intersection, and DNF types.
     * 
     * @return array<ReflectionNamedType>
     */
    private function flattenNamedTypes(?ReflectionType $type): array
    {
        if ($type === null) {
            return [];
        }

        if ($type instanceof ReflectionNamedType) {
            return [$type];
        }

        $types = [];
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $subType) {
                $types = array_merge($types, $this->flattenNamedTypes($subType));
            }
        }

        return $types;
    }

    private function getFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::SRC_DIR));
        $regex = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);
        $files = [];
        foreach ($regex as $match) {
            $files[] = $match[0];
        }
        return $files;
    }

    /**
     * Extract FQCNs from files within a specific root namespace.
     */
    private function getClassesInNamespace(string $namespacePrefix): array
    {
        $classes = [];
        $namespacePrefix = rtrim($namespacePrefix, '\\');
        
        foreach ($this->getFiles() as $file) {
            $content = file_get_contents($file);
            
            // Extract actual declared namespace
            if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatches)) {
                $declaredNamespace = trim($nsMatches[1]);
                
                // Only process files within our target namespace prefix
                if (str_starts_with($declaredNamespace, $namespacePrefix)) {
                    if (preg_match('/(class|interface|enum)\s+(\w+)/', $content, $matches)) {
                        $classes[] = $declaredNamespace . '\\' . $matches[2];
                    }
                }
            }
        }
        return $classes;
    }
}
