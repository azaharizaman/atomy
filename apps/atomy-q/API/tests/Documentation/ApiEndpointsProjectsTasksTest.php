<?php

declare(strict_types=1);

namespace Tests\Documentation;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApiEndpointsProjectsTasksTest extends TestCase
{
    private function getApiEndpointsPath(): string
    {
        $path = base_path('../API_ENDPOINTS.md');
        return realpath($path) ?: base_path('API_ENDPOINTS.md');
    }

    public function test_api_endpoints_document_has_section_28_projects_planned(): void
    {
        $path = $this->getApiEndpointsPath();
        $this->assertTrue(File::exists($path), 'API_ENDPOINTS.md should exist');
        $content = File::get($path);
        $this->assertStringContainsString('## 28. Projects (planned)', $content);
    }

    public function test_api_endpoints_document_has_section_29_tasks_planned(): void
    {
        $path = $this->getApiEndpointsPath();
        $this->assertTrue(File::exists($path), 'API_ENDPOINTS.md should exist');
        $content = File::get($path);
        $this->assertStringContainsString('## 29. Tasks (planned)', $content);
    }

    public function test_section_28_includes_planned_project_endpoints(): void
    {
        $path = $this->getApiEndpointsPath();
        $content = File::get($path);
        $this->assertStringContainsString('/projects`', $content);
        $this->assertStringContainsString('/projects/:id', $content);
        $this->assertStringContainsString('/projects/:id/health', $content);
        $this->assertStringContainsString('/projects/:id/rfqs', $content);
        $this->assertStringContainsString('/projects/:id/tasks', $content);
    }

    public function test_section_29_includes_planned_task_endpoints(): void
    {
        $path = $this->getApiEndpointsPath();
        $content = File::get($path);
        $this->assertStringContainsString('/tasks`', $content);
        $this->assertStringContainsString('/tasks/:id', $content);
        $this->assertStringContainsString('/tasks/:id/dependencies', $content);
    }
}
