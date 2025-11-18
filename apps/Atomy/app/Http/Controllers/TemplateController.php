<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use App\Services\NotificationRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

final class TemplateController extends Controller
{
    public function __construct(
        private readonly NotificationRenderer $renderer,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * List all notification templates.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = NotificationTemplate::query();

            if ($request->has('channel')) {
                $query->where('channel', $request->input('channel'));
            }

            if ($request->has('category')) {
                $query->where('category', $request->input('category'));
            }

            $templates = $query->paginate(50)
                ->through(fn($template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'channel' => $template->channel,
                    'category' => $template->category,
                    'subject' => $template->subject,
                    'created_at' => $template->created_at->toIso8601String(),
                    'updated_at' => $template->updated_at->toIso8601String(),
                ]);

            return response()->json([
                'success' => true,
                'templates' => $templates,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to list templates', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list templates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new notification template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:notification_templates,name',
            'channel' => 'required|string|in:email,sms,push,in_app',
            'category' => 'required|string|in:system,marketing,transactional,security',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'variables.*' => 'string',
        ]);

        try {
            // Validate template syntax
            $this->renderer->validate($validated['body']);
            if (isset($validated['subject'])) {
                $this->renderer->validate($validated['subject']);
            }

            // Extract variables from template
            $extractedVars = $this->renderer->extractVariables($validated['body']);
            if (isset($validated['subject'])) {
                $extractedVars = array_merge(
                    $extractedVars,
                    $this->renderer->extractVariables($validated['subject'])
                );
            }

            $template = NotificationTemplate::create([
                'name' => $validated['name'],
                'channel' => $validated['channel'],
                'category' => $validated['category'],
                'subject' => $validated['subject'] ?? null,
                'body' => $validated['body'],
                'variables' => array_unique($extractedVars),
            ]);

            return response()->json([
                'success' => true,
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'channel' => $template->channel,
                    'category' => $template->category,
                    'subject' => $template->subject,
                    'body' => $template->body,
                    'variables' => $template->variables,
                ],
                'message' => 'Template created successfully',
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid template syntax',
                'error' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to create template', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a notification template.
     */
    public function update(string $templateId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:notification_templates,name,' . $templateId,
            'channel' => 'sometimes|string|in:email,sms,push,in_app',
            'category' => 'sometimes|string|in:system,marketing,transactional,security',
            'subject' => 'nullable|string|max:255',
            'body' => 'sometimes|string',
        ]);

        try {
            $template = NotificationTemplate::findOrFail($templateId);

            // Validate template syntax if body is being updated
            if (isset($validated['body'])) {
                $this->renderer->validate($validated['body']);
            }
            if (isset($validated['subject'])) {
                $this->renderer->validate($validated['subject']);
            }

            // Extract variables if body or subject changed
            $extractedVars = null;
            if (isset($validated['body']) || isset($validated['subject'])) {
                $bodyToCheck = $validated['body'] ?? $template->body;
                $subjectToCheck = $validated['subject'] ?? $template->subject;

                $extractedVars = $this->renderer->extractVariables($bodyToCheck);
                if ($subjectToCheck) {
                    $extractedVars = array_merge(
                        $extractedVars,
                        $this->renderer->extractVariables($subjectToCheck)
                    );
                }
                $extractedVars = array_unique($extractedVars);
            }

            $updateData = array_filter($validated, fn($key) => $key !== 'variables', ARRAY_FILTER_USE_KEY);
            if ($extractedVars !== null) {
                $updateData['variables'] = $extractedVars;
            }

            $template->update($updateData);

            return response()->json([
                'success' => true,
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'channel' => $template->channel,
                    'category' => $template->category,
                    'subject' => $template->subject,
                    'body' => $template->body,
                    'variables' => $template->variables,
                ],
                'message' => 'Template updated successfully',
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid template syntax',
                'error' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to update template', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a notification template.
     */
    public function destroy(string $templateId): JsonResponse
    {
        try {
            $template = NotificationTemplate::findOrFail($templateId);
            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully',
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete template', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview a template with sample variables.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'nullable|string',
            'body' => 'required|string',
            'variables' => 'required|array',
        ]);

        try {
            $rendered = $this->renderer->renderEmail(
                subject: $validated['subject'] ?? null,
                body: $validated['body'],
                variables: $validated['variables']
            );

            return response()->json([
                'success' => true,
                'preview' => $rendered,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to render template',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
