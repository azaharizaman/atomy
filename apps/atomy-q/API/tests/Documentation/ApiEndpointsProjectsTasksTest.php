<?php

declare(strict_types=1);

namespace Tests\Documentation;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApiEndpointsProjectsTasksTest extends TestCase
{
    private string $apiEndpointsPath = '';

    private string $apiEndpointsContent = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiEndpointsPath = $this->getApiEndpointsPath();
        $this->assertTrue(File::exists($this->apiEndpointsPath), 'API_ENDPOINTS.md should exist');
        $this->apiEndpointsContent = File::get($this->apiEndpointsPath);
    }

    private function getApiEndpointsPath(): string
    {
        $path = base_path('../API_ENDPOINTS.md');
        return realpath($path) ?: base_path('API_ENDPOINTS.md');
    }

    public function test_api_endpoints_document_has_section_28_projects_planned(): void
    {
        $this->assertStringContainsString('## 28. Projects (planned)', $this->apiEndpointsContent);
    }

    public function test_api_endpoints_document_has_section_29_tasks_planned(): void
    {
        $this->assertStringContainsString('## 29. Tasks (planned)', $this->apiEndpointsContent);
    }

    public function test_section_28_includes_planned_project_endpoints(): void
    {
        $this->assertStringContainsString('/projects`', $this->apiEndpointsContent);
        $this->assertStringContainsString('/projects/:id', $this->apiEndpointsContent);
        $this->assertStringContainsString('/projects/:id/health', $this->apiEndpointsContent);
        $this->assertStringContainsString('/projects/:id/rfqs', $this->apiEndpointsContent);
        $this->assertStringContainsString('/projects/:id/tasks', $this->apiEndpointsContent);
    }

    public function test_section_29_includes_planned_task_endpoints(): void
    {
        $this->assertStringContainsString('/tasks`', $this->apiEndpointsContent);
        $this->assertStringContainsString('/tasks/:id', $this->apiEndpointsContent);
        $this->assertStringContainsString('/tasks/:id/dependencies', $this->apiEndpointsContent);
    }
}
