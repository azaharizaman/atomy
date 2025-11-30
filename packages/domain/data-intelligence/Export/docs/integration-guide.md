# Integration Guide: Export

This guide demonstrates how to integrate Nexus\Export into Laravel, Symfony, and custom PHP applications.

---

## Laravel Integration

### Step 1: Create Service Provider

Create `app/Providers/ExportServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Export\Services\ExportManager;
use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Export\Core\Formatters\CsvFormatter;
use Nexus\Export\Core\Formatters\JsonFormatter;
use Nexus\Export\Core\Formatters\XmlFormatter;
use Nexus\Export\Core\Formatters\TxtFormatter;
use Nexus\Export\Core\Engine\TemplateRenderer;
use Nexus\Export\Core\Engine\DefinitionValidator;
use Nexus\Export\ValueObjects\ExportFormat;
use App\Services\Export\PdfFormatter;
use App\Services\Export\ExcelFormatter;

class ExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind ExportManager
        $this->app->singleton(ExportManagerInterface::class, function ($app) {
            return new ExportManager(
                validator: new DefinitionValidator(),
                formatters: [
                    ExportFormat::CSV => new CsvFormatter(),
                    ExportFormat::JSON => new JsonFormatter(),
                    ExportFormat::XML => new XmlFormatter(),
                    ExportFormat::TXT => new TxtFormatter(),
                    ExportFormat::PDF => new PdfFormatter(),
                    ExportFormat::EXCEL => new ExcelFormatter(),
                ],
                templateEngine: new TemplateRenderer(),
                logger: $app->make(\Psr\Log\LoggerInterface::class)
            );
        });
    }

    public function boot(): void
    {
        // Optional: Publish config
        $this->publishes([
            __DIR__.'/../../config/export.php' => config_path('export.php'),
        ], 'export-config');
    }
}
```

### Step 2: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\ExportServiceProvider::class,
],
```

### Step 3: Create PDF Formatter (Application Layer)

Create `app/Services/Export/PdfFormatter.php`:

```php
<?php

namespace App\Services\Export;

use Nexus\Export\Contracts\ExportFormatterInterface;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportFormat;
use TCPDF;

final readonly class PdfFormatter implements ExportFormatterInterface
{
    public function __construct(
        private ?TCPDF $pdf = null
    ) {
        $this->pdf = $pdf ?? new TCPDF();
    }

    public function format(ExportDefinition $definition): string
    {
        $this->pdf->AddPage();
        $this->pdf->SetFont('helvetica', '', 12);
        
        // Render metadata
        $metadata = $definition->getMetadata();
        $this->pdf->Cell(0, 10, $metadata->title, 0, 1, 'C');
        
        // Render sections
        foreach ($definition->getStructure() as $section) {
            $this->renderSection($section);
        }
        
        return $this->pdf->Output('', 'S');
    }

    private function renderSection($section): void
    {
        $this->pdf->Ln(5);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, $section->getTitle(), 0, 1);
        $this->pdf->SetFont('helvetica', '', 12);
        
        $content = $section->getContent();
        
        if ($content instanceof \Nexus\Export\ValueObjects\TableStructure) {
            $this->renderTable($content);
        } elseif (is_array($content)) {
            foreach ($content as $key => $value) {
                $this->pdf->Cell(0, 10, "{$key}: {$value}", 0, 1);
            }
        }
    }

    private function renderTable($table): void
    {
        // Render headers
        $this->pdf->SetFillColor(200, 200, 200);
        foreach ($table->headers as $header) {
            $this->pdf->Cell(40, 7, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();
        
        // Render rows
        foreach ($table->rows as $row) {
            foreach ($row as $cell) {
                $this->pdf->Cell(40, 7, (string)$cell, 1);
            }
            $this->pdf->Ln();
        }
        
        // Render footers
        if ($table->footers) {
            $this->pdf->SetFont('helvetica', 'B', 12);
            foreach ($table->footers as $footer) {
                $this->pdf->Cell(40, 7, (string)$footer, 1);
            }
            $this->pdf->Ln();
        }
    }

    public function getFormat(): ExportFormat
    {
        return ExportFormat::PDF;
    }

    public function supportsStreaming(): bool
    {
        return false;
    }

    public function requiresExternalService(): bool
    {
        return false;
    }

    public function getRequiredSchemaVersion(): string
    {
        return '1.0';
    }
}
```

### Step 4: Use in Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportDestination;
use App\Models\Invoice;

class InvoiceExportController extends Controller
{
    public function __construct(
        private readonly ExportManagerInterface $exportManager
    ) {}

    public function exportCsv(string $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        $result = $this->exportManager->export(
            definition: $invoice->toExportDefinition(),
            format: ExportFormat::CSV,
            destination: ExportDestination::DOWNLOAD
        );
        
        return response()->download($result->getFilePath())
            ->deleteFileAfterSend(true);
    }

    public function exportPdf(string $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        $result = $this->exportManager->export(
            definition: $invoice->toExportDefinition(),
            format: ExportFormat::PDF,
            destination: ExportDestination::DOWNLOAD
        );
        
        return response()->download($result->getFilePath())
            ->deleteFileAfterSend(true);
    }

    public function emailExport(Request $request, string $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        $result = $this->exportManager->export(
            definition: $invoice->toExportDefinition(),
            format: ExportFormat::PDF,
            destination: ExportDestination::EMAIL,
            options: [
                'recipient' => $request->input('email'),
                'subject' => "Invoice {$invoice->number}"
            ]
        );
        
        return response()->json([
            'message' => 'Export sent via email',
            'duration' => $result->getDuration()
        ]);
    }
}
```

### Step 5: Define Routes

```php
// routes/web.php
Route::get('/invoices/{id}/export/csv', [InvoiceExportController::class, 'exportCsv']);
Route::get('/invoices/{id}/export/pdf', [InvoiceExportController::class, 'exportPdf']);
Route::post('/invoices/{id}/export/email', [InvoiceExportController::class, 'emailExport']);
```

---

## Symfony Integration

### Step 1: Register Services

Create `config/services.yaml`:

```yaml
services:
    # Export Manager
    Nexus\Export\Contracts\ExportManagerInterface:
        class: Nexus\Export\Services\ExportManager
        arguments:
            $validator: '@Nexus\Export\Contracts\DefinitionValidatorInterface'
            $formatters:
                csv: '@App\Service\Export\CsvFormatter'
                json: '@App\Service\Export\JsonFormatter'
                xml: '@App\Service\Export\XmlFormatter'
                txt: '@App\Service\Export\TxtFormatter'
                pdf: '@App\Service\Export\PdfFormatter'
            $templateEngine: '@Nexus\Export\Contracts\TemplateEngineInterface'
            $logger: '@logger'

    # Validator
    Nexus\Export\Contracts\DefinitionValidatorInterface:
        class: Nexus\Export\Core\Engine\DefinitionValidator

    # Template Engine
    Nexus\Export\Contracts\TemplateEngineInterface:
        class: Nexus\Export\Core\Engine\TemplateRenderer

    # Formatters
    App\Service\Export\CsvFormatter:
        class: Nexus\Export\Core\Formatters\CsvFormatter

    App\Service\Export\JsonFormatter:
        class: Nexus\Export\Core\Formatters\JsonFormatter

    App\Service\Export\XmlFormatter:
        class: Nexus\Export\Core\Formatters\XmlFormatter

    App\Service\Export\TxtFormatter:
        class: Nexus\Export\Core\Formatters\TxtFormatter

    App\Service\Export\PdfFormatter:
        class: App\Service\Export\PdfFormatter
```

### Step 2: Create Controller

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportDestination;

class InvoiceExportController extends AbstractController
{
    public function __construct(
        private readonly ExportManagerInterface $exportManager
    ) {}

    #[Route('/invoices/{id}/export/csv', name: 'invoice_export_csv')]
    public function exportCsv(int $id): BinaryFileResponse
    {
        $invoice = $this->getInvoice($id);
        
        $result = $this->exportManager->export(
            definition: $invoice->toExportDefinition(),
            format: ExportFormat::CSV,
            destination: ExportDestination::DOWNLOAD
        );
        
        return $this->file($result->getFilePath());
    }

    #[Route('/invoices/{id}/export/pdf', name: 'invoice_export_pdf')]
    public function exportPdf(int $id): BinaryFileResponse
    {
        $invoice = $this->getInvoice($id);
        
        $result = $this->exportManager->export(
            definition: $invoice->toExportDefinition(),
            format: ExportFormat::PDF,
            destination: ExportDestination::DOWNLOAD
        );
        
        return $this->file($result->getFilePath());
    }
}
```

---

## Pure PHP Integration

### Step 1: Bootstrap Export Manager

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Nexus\Export\Services\ExportManager;
use Nexus\Export\Core\Formatters\CsvFormatter;
use Nexus\Export\Core\Formatters\JsonFormatter;
use Nexus\Export\Core\Engine\DefinitionValidator;
use Nexus\Export\Core\Engine\TemplateRenderer;
use Nexus\Export\ValueObjects\ExportFormat;
use Psr\Log\NullLogger;

// Create export manager
$exportManager = new ExportManager(
    validator: new DefinitionValidator(),
    formatters: [
        ExportFormat::CSV => new CsvFormatter(),
        ExportFormat::JSON => new JsonFormatter(),
    ],
    templateEngine: new TemplateRenderer(),
    logger: new NullLogger()
);

// Export invoice
$invoice = getInvoice($_GET['id']);
$result = $exportManager->export(
    definition: $invoice->toExportDefinition(),
    format: ExportFormat::CSV,
    destination: ExportDestination::DOWNLOAD
);

// Send file to browser
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="invoice.csv"');
readfile($result->getFilePath());
unlink($result->getFilePath());
```

---

## Integration with Nexus Packages

### With Nexus\Storage

Save export to cloud storage:

```php
use Nexus\Storage\Contracts\StorageInterface;
use Nexus\Export\ValueObjects\ExportDestination;

class StorageExportHandler
{
    public function __construct(
        private readonly ExportManagerInterface $exportManager,
        private readonly StorageInterface $storage
    ) {}

    public function exportToStorage(ExportDefinition $definition, ExportFormat $format): string
    {
        // Generate export
        $result = $this->exportManager->export(
            definition: $definition,
            format: $format,
            destination: ExportDestination::STORAGE
        );

        // Upload to storage
        $filePath = $this->storage->store(
            path: "exports/{$definition->getMetadata()->title}_{$format->value}",
            contents: file_get_contents($result->getFilePath()),
            metadata: [
                'generated_at' => $definition->getMetadata()->generatedAt->format('Y-m-d H:i:s'),
                'format' => $format->value
            ]
        );

        // Clean up temp file
        unlink($result->getFilePath());

        return $filePath;
    }
}
```

### With Nexus\Notifier

Email export to user:

```php
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Export\ValueObjects\ExportDestination;

class EmailExportHandler
{
    public function __construct(
        private readonly ExportManagerInterface $exportManager,
        private readonly NotificationManagerInterface $notifier
    ) {}

    public function emailExport(
        ExportDefinition $definition,
        ExportFormat $format,
        string $recipientEmail
    ): void {
        // Generate export
        $result = $this->exportManager->export(
            definition: $definition,
            format: $format,
            destination: ExportDestination::EMAIL
        );

        // Send via notifier
        $this->notifier->send(
            recipient: $recipientEmail,
            channel: 'email',
            template: 'export.ready',
            data: [
                'title' => $definition->getMetadata()->title,
                'attachment' => $result->getFilePath(),
                'format' => $format->value
            ]
        );

        // Clean up temp file
        unlink($result->getFilePath());
    }
}
```

### With Nexus\AuditLogger

Track export activity:

```php
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;

class AuditedExportHandler
{
    public function __construct(
        private readonly ExportManagerInterface $exportManager,
        private readonly AuditLogManagerInterface $auditLogger
    ) {}

    public function exportWithAudit(
        string $entityId,
        ExportDefinition $definition,
        ExportFormat $format
    ): ExportResult {
        // Log export started
        $this->auditLogger->log(
            entityId: $entityId,
            action: 'export_started',
            description: "Started export to {$format->value}"
        );

        try {
            // Perform export
            $result = $this->exportManager->export(
                definition: $definition,
                format: $format,
                destination: ExportDestination::DOWNLOAD
            );

            // Log success
            $this->auditLogger->log(
                entityId: $entityId,
                action: 'export_completed',
                description: "Export completed in {$result->getDuration()}ms",
                metadata: [
                    'format' => $format->value,
                    'file_size' => filesize($result->getFilePath())
                ]
            );

            return $result;

        } catch (\Exception $e) {
            // Log failure
            $this->auditLogger->log(
                entityId: $entityId,
                action: 'export_failed',
                description: "Export failed: {$e->getMessage()}"
            );

            throw $e;
        }
    }
}
```

---

## Advanced Patterns

### Custom Template Formatter

```php
use Nexus\Export\Contracts\ExportFormatterInterface;
use Nexus\Export\Contracts\TemplateEngineInterface;
use Nexus\Export\ValueObjects\ExportFormat;

class HtmlFormatter implements ExportFormatterInterface
{
    public function __construct(
        private readonly TemplateEngineInterface $templateEngine
    ) {}

    public function format(ExportDefinition $definition): string
    {
        $template = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>{{metadata.title}}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>{{metadata.title}}</h1>
    <p>Generated: {{metadata.generatedAt|date:Y-m-d H:i}}</p>
    
    @foreach(sections as section)
    <section>
        <h2>{{section.title}}</h2>
        @if(section.content.headers)
        <table>
            <thead>
                <tr>
                @foreach(section.content.headers as header)
                    <th>{{header}}</th>
                @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach(section.content.rows as row)
                <tr>
                    @foreach(row as cell)
                    <td>{{cell}}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </section>
    @endforeach
</body>
</html>
HTML;

        return $this->templateEngine->render($template, [
            'metadata' => $definition->getMetadata(),
            'sections' => $definition->getStructure()
        ]);
    }

    public function getFormat(): ExportFormat
    {
        return ExportFormat::HTML;
    }
    
    // ... other methods
}
```

---

## Troubleshooting

### Issue: Memory exhaustion with large exports

**Solution:** Use streaming formats (CSV, JSON):

```php
// CSV automatically streams for > 1000 rows
$result = $exportManager->export(
    definition: $largeDatasetDefinition,
    format: ExportFormat::CSV,  // Streams automatically
    destination: ExportDestination::DOWNLOAD
);
```

### Issue: PDF formatter not working

**Solution:** Install TCPDF or DomPDF:

```bash
composer require tecnickcom/tcpdf
```

### Issue: Excel export fails

**Solution:** Install PhpSpreadsheet:

```bash
composer require phpoffice/phpspreadsheet
```

---

**Last Updated:** 2025-11-24  
**Maintained By:** Nexus Architecture Team
