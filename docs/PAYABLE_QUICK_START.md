# Nexus\Payable Quick Start Guide

## Installation & Setup

### 1. Service Provider Registration
✅ **Already Done**: `PayableServiceProvider` is registered in `bootstrap/app.php`

### 2. Run Database Migrations
```bash
cd /home/user/dev/atomy/apps/Atomy
php artisan migrate
```

This will create 5 tables:
- `vendors` - Vendor master data
- `vendor_bills` - Vendor bill headers
- `vendor_bill_lines` - Bill line items
- `payment_schedules` - Payment scheduling
- `payments` - Payment processing

### 3. Verify Autoloading
```bash
cd /home/user/dev/atomy
composer dump-autoload
```

---

## Testing the Implementation

### Test 1: Create a Vendor
```bash
curl -X POST http://localhost:8000/api/payable/vendors \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "code": "VENDOR001",
    "name": "ABC Supplies Sdn Bhd",
    "status": "active",
    "payment_terms": "net_30",
    "qty_tolerance_percent": 5.0,
    "price_tolerance_percent": 2.0,
    "currency": "MYR",
    "email": "billing@abc.com",
    "tax_id": "C1234567890"
  }'
```

**Expected Response** (201 Created):
```json
{
  "data": {
    "id": "uuid-here",
    "code": "VENDOR001",
    "name": "ABC Supplies Sdn Bhd",
    "status": "active",
    ...
  }
}
```

### Test 2: Submit a Vendor Bill
```bash
curl -X POST http://localhost:8000/api/payable/bills \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "vendor_id": "uuid-from-step-1",
    "bill_number": "INV-2024-001",
    "bill_date": "2024-01-15",
    "due_date": "2024-02-14",
    "currency": "MYR",
    "tax_amount": 0,
    "description": "Office supplies purchase",
    "lines": [
      {
        "description": "Printer Paper A4",
        "quantity": 100,
        "unit_price": 10.50,
        "gl_account": "6100",
        "po_line_reference": "PO-001-L1",
        "grn_line_reference": "GRN-001-L1"
      }
    ]
  }'
```

**Expected Response** (201 Created):
```json
{
  "data": {
    "id": "bill-uuid",
    "bill_number": "INV-2024-001",
    "status": "draft",
    "matching_status": "pending",
    "total_amount": 1050.00,
    ...
  }
}
```

### Test 3: Perform 3-Way Matching
```bash
curl -X POST http://localhost:8000/api/payable/bills/{bill-id}/match \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response** (200 OK):
```json
{
  "data": {
    "status": "matched",
    "matched": true,
    "within_tolerance": true,
    "variances": [],
    "line_results": [
      {
        "line_number": 1,
        "matched": true,
        "qty_variance_percent": 0.0,
        "price_variance_percent": 0.0,
        ...
      }
    ]
  }
}
```

### Test 4: Approve Bill
```bash
curl -X POST http://localhost:8000/api/payable/bills/{bill-id}/approve \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test 5: Post to GL
```bash
curl -X POST http://localhost:8000/api/payable/bills/{bill-id}/post-to-gl \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response**:
```json
{
  "data": {
    "gl_journal_id": "journal-uuid",
    "message": "Bill posted to general ledger successfully"
  }
}
```

### Test 6: Schedule Payment
```bash
curl -X POST http://localhost:8000/api/payable/bills/{bill-id}/schedule-payment \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test 7: Process Payment
```bash
curl -X POST http://localhost:8000/api/payable/payments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "payment_date": "2024-02-14",
    "amount": 1050.00,
    "currency": "MYR",
    "payment_method": "bank_transfer",
    "bank_account": "1010",
    "reference": "TXN-20240214-001",
    "allocations": [
      {
        "bill_id": "bill-uuid",
        "amount": 1050.00
      }
    ]
  }'
```

---

## CSV Import Format

### Sample CSV Structure
```csv
vendor_id,bill_number,bill_date,due_date,currency,description,lines
uuid-here,INV-001,2024-01-15,2024-02-14,MYR,Office supplies,"[{""description"":""Paper"",""quantity"":100,""unit_price"":10.50,""gl_account"":""6100"",""po_line_reference"":""PO-001-L1"",""grn_line_reference"":""GRN-001-L1""}]"
```

### Import Endpoint
```bash
curl -X POST http://localhost:8000/api/payable/bills/import-csv \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@bills.csv"
```

---

## Vendor Aging Report

```bash
curl -X GET "http://localhost:8000/api/payable/vendors/{vendor-id}/aging?as_of_date=2024-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response**:
```json
{
  "data": {
    "current": 1050.00,
    "1_30_days": 0.00,
    "31_60_days": 0.00,
    "61_90_days": 0.00,
    "over_90_days": 0.00,
    "total": 1050.00
  }
}
```

---

## Integration with Other Nexus Modules

### Required Nexus\Finance Setup
The Payable module posts GL journals via `Nexus\Finance\Contracts\FinanceManagerInterface`. Ensure:

1. Finance module is installed and configured
2. GL accounts are set up:
   - **2100**: Accounts Payable Control (credit on bill post, debit on payment)
   - **6100**: Expense accounts (debit on bill post)
   - **1010**: Bank accounts (credit on payment)

### Required Nexus\Currency Setup
For multi-currency support:

1. Currency module is installed
2. Exchange rates are configured via `Nexus\Currency\Contracts\RateProviderInterface`
3. Base currency is set to **MYR** (or configure via environment)

### Required Nexus\Period Setup
For fiscal period validation:

1. Period module is installed
2. Fiscal periods are created and opened
3. Bills can only be posted to open periods

### Optional: Nexus\Procurement Integration
For 3-way matching against purchase orders:

1. Procurement module is installed
2. Purchase orders are created with line references
3. Bill lines include `po_line_reference` field

### Optional: Nexus\Inventory Integration
For 3-way matching against goods received:

1. Inventory module is installed
2. GRNs are created with line references
3. Bill lines include `grn_line_reference` field

---

## Troubleshooting

### Issue: "Vendor with code 'VENDOR001' already exists"
**Solution**: Vendor codes must be unique per tenant. Use a different code or update the existing vendor.

### Issue: "Cannot submit bill: Period ... is not open"
**Solution**: Ensure the fiscal period for the bill date is open in Nexus\Period.

### Issue: "3-way matching failed: PO line 'PO-001-L1' not found"
**Solution**: Ensure the PO line reference exists in Nexus\Procurement. Or submit bill without PO/GRN references if matching is not required.

### Issue: "Payment allocation failed: Total allocation exceeds payment amount"
**Solution**: Ensure the sum of all bill allocations does not exceed the payment amount.

### Issue: "Bill posted to GL successfully but no journal visible"
**Solution**: Check Nexus\Finance to ensure the journal was created. The `gl_journal_id` should be returned.

---

## API Authentication

All API endpoints require authentication via Laravel Sanctum. To test:

1. Create a user in the database
2. Generate a Sanctum token:
   ```php
   $user = User::find(1);
   $token = $user->createToken('test-token')->plainTextToken;
   ```
3. Use the token in requests:
   ```
   Authorization: Bearer {token}
   ```

---

## Configuration (Optional)

Create `config/payable.php` for custom configuration:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GL Account Configuration
    |--------------------------------------------------------------------------
    */
    'gl_accounts' => [
        'ap_control' => env('PAYABLE_AP_CONTROL_ACCOUNT', '2100'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'default_currency' => env('PAYABLE_DEFAULT_CURRENCY', 'MYR'),
    'default_payment_terms' => env('PAYABLE_DEFAULT_PAYMENT_TERMS', 'net_30'),

    /*
    |--------------------------------------------------------------------------
    | Tolerance Defaults
    |--------------------------------------------------------------------------
    */
    'default_qty_tolerance_percent' => env('PAYABLE_QTY_TOLERANCE', 5.0),
    'default_price_tolerance_percent' => env('PAYABLE_PRICE_TOLERANCE', 2.0),
];
```

---

## Next Steps

1. **Run Migrations**: `php artisan migrate`
2. **Test Vendor Creation**: Use the API endpoints to create a vendor
3. **Test Bill Submission**: Submit a test bill
4. **Test 3-Way Matching**: Perform matching (requires PO/GRN setup)
5. **Test Payment Processing**: Process a payment for the bill
6. **Check GL Integration**: Verify journals in Nexus\Finance
7. **Write Unit Tests**: Create tests for critical business logic
8. **Write API Tests**: Test all 16 API endpoints
9. **Document Business Processes**: Create user guides for AP staff

---

## Support

For issues or questions:
1. Check the comprehensive documentation in `docs/PAYABLE_IMPLEMENTATION_SUMMARY.md`
2. Review the package README at `packages/Payable/README.md`
3. Check error logs in `storage/logs/laravel.log`
4. Enable debug mode in `.env`: `APP_DEBUG=true`

---

## File Locations

- **Package**: `packages/Payable/`
- **Models**: `apps/Atomy/app/Models/`
- **Repositories**: `apps/Atomy/app/Repositories/`
- **Controllers**: `apps/Atomy/app/Http/Controllers/Api/`
- **Migrations**: `apps/Atomy/database/migrations/`
- **Routes**: `apps/Atomy/routes/api.php`
- **Service Provider**: `apps/Atomy/app/Providers/PayableServiceProvider.php`
