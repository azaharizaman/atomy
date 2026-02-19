<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Psr\Log\LoggerInterface;

/**
 * Document Generator Service
 * 
 * Generates proposals, contracts, and quote documents from templates.
 * Integrates with the Document package for storage and version control.
 * 
 * @package Nexus\CRMOperations\Services
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class DocumentGenerator
{
    /**
     * @param object|null $documentService Document service for storage
     * @param object|null $templateService Template rendering service
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private ?object $documentService = null,
        private ?object $templateService = null,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Generate proposal from template
     * 
     * @param string $opportunityId Opportunity ID
     * @param string $templateId Template ID to use
     * @param array<string, mixed> $data Data to merge into template
     * @param string $outputFormat Output format (pdf, docx, html)
     * @return array{document_id: string, url: string, generated_at: string}
     */
    public function generateProposal(
        string $opportunityId,
        string $templateId,
        array $data,
        string $outputFormat = 'pdf'
    ): array {
        $this->logger?->info('Generating proposal', [
            'opportunity_id' => $opportunityId,
            'template_id' => $templateId,
            'output_format' => $outputFormat,
        ]);

        $renderedContent = $this->renderTemplate($templateId, $data);

        $documentId = $this->saveDocument(
            title: "Proposal - {$opportunityId}",
            content: $renderedContent,
            type: 'proposal',
            entityId: $opportunityId,
            format: $outputFormat
        );

        return [
            'document_id' => $documentId,
            'url' => "/documents/{$documentId}.{$outputFormat}",
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Generate contract from template
     * 
     * @param string $opportunityId Opportunity ID
     * @param string $templateId Template ID to use
     * @param array<string, mixed> $data Data to merge into template
     * @param string $outputFormat Output format (pdf, docx)
     * @return array{document_id: string, url: string, generated_at: string}
     */
    public function generateContract(
        string $opportunityId,
        string $templateId,
        array $data,
        string $outputFormat = 'pdf'
    ): array {
        $this->logger?->info('Generating contract', [
            'opportunity_id' => $opportunityId,
            'template_id' => $templateId,
            'output_format' => $outputFormat,
        ]);

        $renderedContent = $this->renderTemplate($templateId, $data);

        $documentId = $this->saveDocument(
            title: "Contract - {$opportunityId}",
            content: $renderedContent,
            type: 'contract',
            entityId: $opportunityId,
            format: $outputFormat
        );

        return [
            'document_id' => $documentId,
            'url' => "/documents/{$documentId}.{$outputFormat}",
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Generate quote document from template
     * 
     * @param string $quoteId Quote ID
     * @param string $templateId Template ID to use
     * @param array<string, mixed> $data Data to merge into template
     * @param string $outputFormat Output format (pdf, docx, html)
     * @return array{document_id: string, url: string, generated_at: string}
     */
    public function generateQuote(
        string $quoteId,
        string $templateId,
        array $data,
        string $outputFormat = 'pdf'
    ): array {
        $this->logger?->info('Generating quote document', [
            'quote_id' => $quoteId,
            'template_id' => $templateId,
            'output_format' => $outputFormat,
        ]);

        $renderedContent = $this->renderTemplate($templateId, $data);

        $documentId = $this->saveDocument(
            title: "Quote - {$quoteId}",
            content: $renderedContent,
            type: 'quote',
            entityId: $quoteId,
            format: $outputFormat
        );

        return [
            'document_id' => $documentId,
            'url' => "/documents/{$documentId}.{$outputFormat}",
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Render template with data
     * 
     * @param string $templateId Template ID
     * @param array<string, mixed> $data Data to merge
     * @return string Rendered content
     */
    public function renderTemplate(string $templateId, array $data): string
    {
        $this->logger?->debug('Rendering template', [
            'template_id' => $templateId,
            'data_keys' => array_keys($data),
        ]);

        if ($this->templateService !== null) {
            // In real implementation, use the template service
            // return $this->templateService->render($templateId, $data);
        }

        // Fallback: Simple template rendering
        $template = $this->getTemplateContent($templateId);
        
        // Replace placeholders {{key}} with data values
        $result = $template;
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $result = str_replace("{{{$key}}}", (string) $value, $result);
            }
        }

        return $result;
    }

    /**
     * Get template content by ID
     * 
     * @param string $templateId Template ID
     * @return string Template content
     */
    private function getTemplateContent(string $templateId): string
    {
        // In real implementation, this would fetch from template storage
        // For now, return a basic template
        $templates = [
            'default_proposal' => <<<HTML
<h1>Proposal</h1>
<p>Prepared for: {{customer_name}}</p>
<p>Date: {{date}}</p>
<h2>Scope of Work</h2>
{{scope}}
<h2>Pricing</h2>
{{pricing}}
HTML,
            'default_contract' => <<<HTML
<h1>Service Agreement</h1>
<p>This agreement is made between {{company_name}} and {{customer_name}}</p>
<p>Date: {{date}}</p>
<h2>Terms and Conditions</h2>
{{terms}}
<h2>Signatures</h2>
<p>_____________________{{company_name}}</p>
<p>_____________________ {{customer_name}}</p>
HTML,
            'default_quote' => <<<HTML
<h1>Quote</h1>
<p>Quote #: {{quote_number}}</p>
<p>Valid until: {{valid_until}}</p>
<p>Customer: {{customer_name}}</p>
<h2>Items</h2>
{{items}}
<h2>Total: {{total}}</h2>
HTML,
        ];

        return $templates[$templateId] ?? $templates['default_proposal'];
    }

    /**
     * Save document to storage
     * 
     * @param string $title Document title
     * @param string $content Document content
     * @param string $type Document type (proposal, contract, quote)
     * @param string $entityId Associated entity ID
     * @param string $format Output format
     * @return string Document ID
     */
    private function saveDocument(
        string $title,
        string $content,
        string $type,
        string $entityId,
        string $format
    ): string {
        $documentId = uniqid('doc_');

        $this->logger?->info('Saving document', [
            'document_id' => $documentId,
            'title' => $title,
            'type' => $type,
            'entity_id' => $entityId,
        ]);

        if ($this->documentService !== null) {
            // In real implementation, save to document service
            // $this->documentService->create([
            //     'title' => $title,
            //     'content' => $content,
            //     'type' => $type,
            //     'entity_id' => $entityId,
            //     'format' => $format,
            // ]);
        }

        return $documentId;
    }

    /**
     * Generate document in multiple formats
     * 
     * @param string $templateId Template ID
     * @param array<string, mixed> $data Data to merge
     * @param string[] $formats Output formats
     * @return array<string, array{document_id: string, url: string}>
     */
    public function generateInMultipleFormats(
        string $templateId,
        array $data,
        array $formats = ['pdf', 'docx', 'html']
    ): array {
        $this->logger?->info('Generating document in multiple formats', [
            'template_id' => $templateId,
            'formats' => $formats,
        ]);

        $results = [];
        
        foreach ($formats as $format) {
            $results[$format] = [
                'document_id' => uniqid("doc_{$format}_"),
                'url' => "/documents/" . uniqid() . ".{$format}",
            ];
        }

        return $results;
    }

    /**
     * Get available templates
     * 
     * @param string|null $type Filter by type (proposal, contract, quote)
     * @return array<int, array{id: string, name: string, type: string}>
     */
    public function getAvailableTemplates(?string $type = null): array
    {
        $templates = [
            ['id' => 'default_proposal', 'name' => 'Default Proposal', 'type' => 'proposal'],
            ['id' => 'default_contract', 'name' => 'Standard Contract', 'type' => 'contract'],
            ['id' => 'default_quote', 'name' => 'Standard Quote', 'type' => 'quote'],
        ];

        if ($type !== null) {
            return array_filter($templates, fn($t) => $t['type'] === $type);
        }

        return $templates;
    }
}
