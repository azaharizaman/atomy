<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

/**
 * Template Service Interface
 * 
 * Interface for template rendering services.
 * 
 * @package Nexus\CRMOperations\Contracts
 */
interface TemplateServiceInterface
{
    /**
     * Render a template with data
     * 
     * @param string $templateId Template ID
     * @param array<string, mixed> $data Data to merge into template
     * @return string Rendered content
     */
    public function render(string $templateId, array $data): string;

    /**
     * Get template content by ID
     * 
     * @param string $templateId Template ID
     * @return string|null Template content or null if not found
     */
    public function getContent(string $templateId): ?string;

    /**
     * List available templates
     * 
     * @param string|null $type Filter by type
     * @return array<int, array{id: string, name: string, type: string}>
     */
    public function list(?string $type = null): array;
}
