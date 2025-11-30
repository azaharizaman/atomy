# Test Suite Summary: DataProcessor

**Package:** `Nexus\DataProcessor`  
**Package Type:** Pure Contract Package (Interface-Only)  
**Last Updated:** 2025-11-24  
**Status:** ✅ Testing Strategy Documented

---

## Important Note on Testing Strategy

**This is a pure contract package (interface-only).** There are no concrete implementations to test within this package. All testing occurs in the **application layer** where interfaces are bound to concrete implementations.

---

## Testing Strategy

### Contract Package Testing Philosophy

For interface-only packages like `Nexus\DataProcessor`, testing is distributed across two layers:

1. **Package Layer (This Package):**  
   - **What to Test:** Interface contracts are syntactically correct  
   - **How to Test:** Static analysis, PHPStan, interface method signature validation  
   - **Location:** Minimal validation tests (if any)

2. **Application Layer (apps/Atomy):**  
   - **What to Test:** Concrete implementations conform to contracts  
   - **How to Test:** Unit tests with mocks, integration tests with real vendors  
   - **Location:** `apps/Atomy/tests/Unit/DataProcessor/`

---

## Package-Level Testing (Minimal)

### Static Analysis

**Tool:** PHPStan Level 9  
**Purpose:** Validate interface method signatures, type declarations

**What is Validated:**
- ✅ Interface methods have complete type declarations
- ✅ Method parameters use proper type hints
- ✅ Return types are declared
- ✅ Exception docblocks are accurate
- ✅ Value object properties are readonly
- ✅ Strict types declaration present

**Example:**
```bash
composer require --dev phpstan/phpstan
phpstan analyse src --level=9
```

---

## Application-Level Testing (Where Real Tests Live)

### 1. Interface Compliance Tests

**Purpose:** Ensure vendor implementations honor the contract

**Location:** `apps/Atomy/tests/Unit/DataProcessor/`

**Example Test:**
```php
namespace Tests\Unit\DataProcessor;

use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use App\Services\DataProcessor\AzureFormRecognizerAdapter;

final class AzureAdapterContractTest extends TestCase
{
    public function test_implements_document_recognizer_interface(): void
    {
        $adapter = new AzureFormRecognizerAdapter(/* deps */);
        
        $this->assertInstanceOf(
            DocumentRecognizerInterface::class,
            $adapter
        );
    }
    
    public function test_recognize_document_returns_processing_result(): void
    {
        $adapter = $this->createMockedAdapter();
        
        $result = $adapter->recognizeDocument(
            '/tmp/test-invoice.pdf',
            'invoice'
        );
        
        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertIsArray($result->getExtractedData());
        $this->assertGreaterThanOrEqual(0, $result->getConfidence());
        $this->assertLessThanOrEqual(100, $result->getConfidence());
    }
}
```

### 2. Mock Implementation Tests

**Purpose:** Test consuming packages using mocked OCR

**Location:** Package tests (e.g., `packages/Payable/tests/`)

**Example Test:**
```php
namespace Nexus\Payable\Tests\Unit;

use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\DataProcessor\ValueObjects\ProcessingResult;

final class VendorBillOcrTest extends TestCase
{
    public function test_create_bill_from_ocr_result(): void
    {
        // Mock the OCR interface
        $ocrMock = $this->createMock(DocumentRecognizerInterface::class);
        
        $ocrMock->method('recognizeDocument')
            ->willReturn(new ProcessingResult(
                extractedData: [
                    'vendor_name' => 'Acme Corp',
                    'invoice_number' => 'INV-001',
                    'total_amount' => 1500.00,
                    'invoice_date' => '2025-11-15',
                ],
                confidence: 95.5,
                fieldConfidences: [
                    'vendor_name' => 98.0,
                    'invoice_number' => 100.0,
                    'total_amount' => 92.0,
                    'invoice_date' => 94.0,
                ],
                warnings: []
            ));
        
        $billManager = new VendorBillManager(
            ocr: $ocrMock,
            repository: $this->createMock(VendorBillRepositoryInterface::class)
        );
        
        $bill = $billManager->createFromScannedDocument('/tmp/bill.pdf');
        
        $this->assertEquals('Acme Corp', $bill->getVendorName());
        $this->assertEquals('INV-001', $bill->getInvoiceNumber());
        $this->assertEquals(1500.00, $bill->getTotalAmount());
    }
}
```

### 3. Integration Tests (Application Layer)

**Purpose:** Test real vendor APIs

**Location:** `apps/Atomy/tests/Integration/DataProcessor/`

**Example Test:**
```php
namespace Tests\Integration\DataProcessor;

final class AzureFormRecognizerIntegrationTest extends TestCase
{
    public function test_recognize_real_invoice(): void
    {
        if (!env('AZURE_FORM_RECOGNIZER_KEY')) {
            $this->markTestSkipped('Azure credentials not configured');
        }
        
        $adapter = app(DocumentRecognizerInterface::class);
        
        $result = $adapter->recognizeDocument(
            __DIR__ . '/fixtures/sample-invoice.pdf',
            'invoice'
        );
        
        $this->assertGreaterThan(80, $result->getConfidence());
        $this->assertArrayHasKey('invoice_number', $result->getExtractedData());
        $this->assertArrayHasKey('total_amount', $result->getExtractedData());
    }
}
```

---

## Test Coverage Expectations

### Package Layer (This Package)
- **Line Coverage:** N/A (no implementation code)
- **Interface Coverage:** 100% (static analysis validates all interfaces)
- **Exception Coverage:** 100% (exceptions are throwable, no logic to test)

### Application Layer (Where Coverage Matters)
- **Adapter Implementation Coverage:** 90%+ (test all interface methods)
- **Edge Case Coverage:** 85%+ (low confidence, missing fields, errors)
- **Integration Coverage:** 70%+ (real vendor API calls)

---

## Testing Checklist for Application Layer

When implementing a vendor adapter, ensure tests cover:

### Interface Contract Compliance
- [ ] Implements `DocumentRecognizerInterface`
- [ ] `recognizeDocument()` returns `ProcessingResult`
- [ ] `getSupportedDocumentTypes()` returns non-empty array
- [ ] `supportsDocumentType()` validates correctly

### Exception Handling
- [ ] Throws `ProcessingFailedException` on OCR errors
- [ ] Throws `UnsupportedDocumentTypeException` for invalid types
- [ ] Vendor-specific errors wrapped in package exceptions

### ProcessingResult Validation
- [ ] `extractedData` is properly formatted array
- [ ] `confidence` is between 0-100
- [ ] `fieldConfidences` match extractedData keys
- [ ] `warnings` array populated for non-critical issues

### Edge Cases
- [ ] Handles corrupted files gracefully
- [ ] Handles unsupported file formats
- [ ] Handles empty/blank documents
- [ ] Handles extremely low confidence results
- [ ] Handles missing required fields

### Performance
- [ ] Processes typical invoice in < 5 seconds
- [ ] Handles large PDFs (10+ pages)
- [ ] Memory usage stays reasonable

---

## Mock Factories for Consuming Packages

### Example Mock Factory

```php
namespace Nexus\DataProcessor\Testing;

use Nexus\DataProcessor\Contracts\DocumentRecognizerInterface;
use Nexus\DataProcessor\ValueObjects\ProcessingResult;

final class MockDocumentRecognizer implements DocumentRecognizerInterface
{
    public function __construct(
        private readonly array $mockData = [],
        private readonly float $mockConfidence = 95.0,
    ) {}
    
    public function recognizeDocument(
        string $filePath,
        string $documentType,
        array $options = []
    ): ProcessingResult {
        return new ProcessingResult(
            extractedData: $this->mockData,
            confidence: $this->mockConfidence,
            fieldConfidences: array_fill_keys(
                array_keys($this->mockData),
                $this->mockConfidence
            ),
            warnings: []
        );
    }
    
    public function getSupportedDocumentTypes(): array
    {
        return ['invoice', 'receipt', 'id', 'passport'];
    }
    
    public function supportsDocumentType(string $documentType): bool
    {
        return in_array($documentType, $this->getSupportedDocumentTypes());
    }
}
```

**Usage in Tests:**
```php
$mockOcr = new MockDocumentRecognizer(
    mockData: ['vendor_name' => 'Acme Corp', 'total' => 1500],
    mockConfidence: 92.5
);

$manager = new VendorBillManager($mockOcr, $repository);
```

---

## How to Run Tests (Application Layer)

### Run Adapter Unit Tests
```bash
cd apps/Atomy
php artisan test --filter=DataProcessor
```

### Run Integration Tests (with real APIs)
```bash
cd apps/Atomy
php artisan test --group=integration --filter=DataProcessor
```

### Run All Tests with Coverage
```bash
cd apps/Atomy
XDEBUG_MODE=coverage php artisan test --coverage --min=80
```

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: DataProcessor Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: xdebug
      
      - name: Install Dependencies
        run: composer install
      
      - name: Run PHPStan (Interface Validation)
        run: vendor/bin/phpstan analyse packages/DataProcessor/src --level=9
      
      - name: Run Application Layer Tests
        working-directory: apps/Atomy
        run: php artisan test --filter=DataProcessor --coverage
```

---

## Known Test Gaps (Application Layer)

### Areas Needing More Coverage

1. **Multi-page Document Processing**  
   - Current: Basic single-page tests  
   - Needed: Tests for 10+ page PDFs

2. **Concurrent Processing**  
   - Current: Sequential processing tests  
   - Needed: Parallel batch processing tests

3. **Vendor API Failures**  
   - Current: Basic error handling  
   - Needed: Rate limiting, timeout, network error scenarios

4. **Performance Benchmarks**  
   - Current: No performance tests  
   - Needed: Baseline benchmarks for each vendor adapter

---

## Testing Best Practices

### 1. Always Mock in Package Tests
When testing packages that consume `DataProcessor`, **always mock** the interface:

```php
// ✅ CORRECT: Mock the interface
$ocrMock = $this->createMock(DocumentRecognizerInterface::class);
```

```php
// ❌ WRONG: Don't use real implementations in package tests
$ocr = new AzureFormRecognizerAdapter(); // Violates package isolation
```

### 2. Test Contract Conformance in Application Layer
Ensure vendor adapters implement the full contract:

```php
public function test_adapter_conforms_to_contract(): void
{
    $adapter = new AzureFormRecognizerAdapter(/* deps */);
    
    $reflection = new \ReflectionClass($adapter);
    $this->assertTrue($reflection->implementsInterface(
        DocumentRecognizerInterface::class
    ));
}
```

### 3. Test ProcessingResult Immutability
Verify value object cannot be modified:

```php
public function test_processing_result_is_immutable(): void
{
    $result = new ProcessingResult(
        extractedData: ['field' => 'value'],
        confidence: 95.0,
        fieldConfidences: ['field' => 95.0],
        warnings: []
    );
    
    $reflection = new \ReflectionClass($result);
    
    foreach ($reflection->getProperties() as $property) {
        $this->assertTrue($property->isReadOnly());
    }
}
```

---

## Summary

**Testing Philosophy:** Pure contract packages delegate all testing to the application layer where concrete implementations exist.

**Package Responsibility:** Ensure interfaces are well-defined via static analysis.

**Application Responsibility:** Test concrete implementations thoroughly with unit, integration, and performance tests.

**Key Takeaway:** The absence of tests in this package **is by design** - testing happens where the actual business logic lives (vendor adapters in the application layer).

---

**Test Strategy:** ✅ Documented  
**Application Tests:** To be implemented in `apps/Atomy/tests/`  
**Static Analysis:** PHPStan Level 9 recommended  
**Maintained By:** Nexus Architecture Team
