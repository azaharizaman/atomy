# Tax Audit Log Database Schema

**Package:** Nexus\Tax  
**Purpose:** Reference schema for tax calculation audit trail and temporal rate management  
**Last Updated:** 2025-11-24

This document provides the complete SQL schema for implementing the tax system database tables in your application layer. The package defines interfaces only; you create these tables in your consuming application.

---

## Table of Contents

- [Schema Overview](#schema-overview)
- [Tax Rates Table](#tax-rates-table)
- [Tax Audit Log Table](#tax-audit-log-table)
- [Exemption Certificates Table](#exemption-certificates-table)
- [Nexus Thresholds Table](#nexus-thresholds-table)
- [Indexes and Performance](#indexes-and-performance)
- [Retention Policies](#retention-policies)

---

## Schema Overview

**Required Tables:**
1. `tax_rates` - Temporal tax rate definitions
2. `tax_audit_log` - Immutable calculation audit trail
3. `exemption_certificates` - Tax exemption certificates
4. `nexus_thresholds` - Economic nexus revenue/transaction thresholds

**Design Principles:**
- **Multi-Tenant:** All tables include `tenant_id` for data isolation
- **Temporal:** Tax rates use `effective_from` and `effective_to` for historical accuracy
- **Immutable Audit Log:** No UPDATE/DELETE on `tax_audit_log` (contra-transactions for corrections)
- **UUID Primary Keys:** ULID strings for distributed systems
- **BCMath Precision:** DECIMAL(15,4) for all monetary amounts

---

## Tax Rates Table

### Purpose
Store temporal tax rate definitions with effective date ranges.

### SQL DDL (PostgreSQL)

```sql
CREATE TABLE tax_rates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    
    -- Tax Rate Identification
    tax_code VARCHAR(50) NOT NULL,
    tax_type VARCHAR(20) NOT NULL CHECK (tax_type IN ('vat', 'gst', 'sst', 'sales_tax', 'excise', 'withholding')),
    tax_level VARCHAR(20) NOT NULL CHECK (tax_level IN ('federal', 'state', 'local', 'municipal')),
    jurisdiction_code VARCHAR(50),
    
    -- Rate Definition
    rate_percentage DECIMAL(10, 4) NOT NULL CHECK (rate_percentage >= 0),
    
    -- Temporal Validity
    effective_from DATE NOT NULL,
    effective_to DATE,
    
    -- GL Integration
    gl_account_code VARCHAR(50) NOT NULL,
    
    -- Compound Tax Sequencing
    application_order INTEGER NOT NULL DEFAULT 1 CHECK (application_order >= 1),
    
    -- Metadata
    description TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(50),
    
    -- Constraints
    CONSTRAINT chk_effective_dates CHECK (effective_to IS NULL OR effective_to >= effective_from),
    CONSTRAINT uq_tax_rate_temporal UNIQUE (tenant_id, tax_code, effective_from)
);

-- Comments
COMMENT ON TABLE tax_rates IS 'Temporal tax rate definitions with effective date ranges';
COMMENT ON COLUMN tax_rates.effective_from IS 'Start date of tax rate validity (NOT NULL - required for temporal queries)';
COMMENT ON COLUMN tax_rates.effective_to IS 'End date of tax rate validity (NULL = open-ended)';
COMMENT ON COLUMN tax_rates.application_order IS 'Sequence for compound taxes (1=first, 2=second, etc.)';
COMMENT ON COLUMN tax_rates.rate_percentage IS 'Tax rate as percentage (7.2500 = 7.25%)';
```

### MySQL Variant

```sql
CREATE TABLE tax_rates (
    id CHAR(26) PRIMARY KEY, -- ULID
    tenant_id CHAR(26) NOT NULL,
    
    tax_code VARCHAR(50) NOT NULL,
    tax_type ENUM('vat', 'gst', 'sst', 'sales_tax', 'excise', 'withholding') NOT NULL,
    tax_level ENUM('federal', 'state', 'local', 'municipal') NOT NULL,
    jurisdiction_code VARCHAR(50),
    
    rate_percentage DECIMAL(10, 4) NOT NULL,
    
    effective_from DATE NOT NULL,
    effective_to DATE,
    
    gl_account_code VARCHAR(50) NOT NULL,
    application_order INT NOT NULL DEFAULT 1,
    
    description TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(50),
    
    UNIQUE KEY uq_tax_rate_temporal (tenant_id, tax_code, effective_from),
    INDEX idx_tenant (tenant_id),
    INDEX idx_tax_code (tax_code),
    INDEX idx_jurisdiction (jurisdiction_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Sample Data

```sql
-- US California Sales Tax (7.25%)
INSERT INTO tax_rates (id, tenant_id, tax_code, tax_type, tax_level, jurisdiction_code, rate_percentage, effective_from, effective_to, gl_account_code, application_order)
VALUES ('01HF7XZQK3YZ8PM2NV6TGQR9WC', '01HF7XZQK3YZ8PM2NV6TGQR9WA', 'US-CA-SALES', 'sales_tax', 'state', 'US-CA', 7.2500, '2024-01-01', NULL, '2210', 1);

-- EU Germany VAT (19%)
INSERT INTO tax_rates (id, tenant_id, tax_code, tax_type, tax_level, jurisdiction_code, rate_percentage, effective_from, effective_to, gl_account_code, application_order)
VALUES ('01HF7XZQK3YZ8PM2NV6TGQR9WD', '01HF7XZQK3YZ8PM2NV6TGQR9WA', 'EU-DE-VAT-STANDARD', 'vat', 'federal', 'DE', 19.0000, '2020-01-01', NULL, '2310', 1);

-- Malaysian SST (Sales Tax 10%, Service Tax 6% - compound)
INSERT INTO tax_rates (id, tenant_id, tax_code, tax_type, tax_level, jurisdiction_code, rate_percentage, effective_from, effective_to, gl_account_code, application_order)
VALUES 
    ('01HF7XZQK3YZ8PM2NV6TGQR9WE', '01HF7XZQK3YZ8PM2NV6TGQR9WA', 'MY-SALES-STANDARD', 'sst', 'federal', 'MY', 10.0000, '2024-01-01', NULL, '2410', 1),
    ('01HF7XZQK3YZ8PM2NV6TGQR9WF', '01HF7XZQK3YZ8PM2NV6TGQR9WA', 'MY-SERVICE-STANDARD', 'sst', 'federal', 'MY', 6.0000, '2024-01-01', NULL, '2420', 2);
```

---

## Tax Audit Log Table

### Purpose
Immutable audit trail of all tax calculations (COMPLIANCE REQUIREMENT).

### SQL DDL (PostgreSQL)

```sql
CREATE TABLE tax_audit_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    
    -- Transaction Reference
    transaction_id VARCHAR(50) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL, -- 'customer_invoice', 'vendor_bill', etc.
    transaction_date DATE NOT NULL,
    
    -- Tax Calculation Input
    tax_code VARCHAR(50) NOT NULL,
    context JSONB NOT NULL, -- Full TaxContext VO serialized
    
    -- Tax Calculation Output
    taxable_amount DECIMAL(15, 4) NOT NULL,
    tax_amount DECIMAL(15, 4) NOT NULL,
    currency_code CHAR(3) NOT NULL,
    tax_breakdown JSONB NOT NULL, -- Full TaxBreakdown VO serialized
    
    -- Adjustment Tracking
    is_adjustment BOOLEAN NOT NULL DEFAULT FALSE,
    original_transaction_id VARCHAR(50),
    adjustment_reason TEXT,
    
    -- Audit Metadata
    calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    calculated_by VARCHAR(50),
    
    -- Constraints
    CONSTRAINT chk_adjustment_reference CHECK (
        (is_adjustment = FALSE AND original_transaction_id IS NULL) OR
        (is_adjustment = TRUE AND original_transaction_id IS NOT NULL)
    )
);

-- Comments
COMMENT ON TABLE tax_audit_log IS 'Immutable audit trail of tax calculations (NO UPDATE/DELETE)';
COMMENT ON COLUMN tax_audit_log.context IS 'Full TaxContext VO (addresses, jurisdiction, exemption)';
COMMENT ON COLUMN tax_audit_log.tax_breakdown IS 'Complete TaxBreakdown with hierarchical tax lines';
COMMENT ON COLUMN tax_audit_log.is_adjustment IS 'TRUE if this is a contra-transaction correction';
```

### MySQL Variant

```sql
CREATE TABLE tax_audit_log (
    id CHAR(26) PRIMARY KEY,
    tenant_id CHAR(26) NOT NULL,
    
    transaction_id VARCHAR(50) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    transaction_date DATE NOT NULL,
    
    tax_code VARCHAR(50) NOT NULL,
    context JSON NOT NULL,
    
    taxable_amount DECIMAL(15, 4) NOT NULL,
    tax_amount DECIMAL(15, 4) NOT NULL,
    currency_code CHAR(3) NOT NULL,
    tax_breakdown JSON NOT NULL,
    
    is_adjustment BOOLEAN NOT NULL DEFAULT 0,
    original_transaction_id VARCHAR(50),
    adjustment_reason TEXT,
    
    calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    calculated_by VARCHAR(50),
    
    INDEX idx_tenant_date (tenant_id, transaction_date),
    INDEX idx_transaction (transaction_id),
    INDEX idx_tax_code_date (tenant_id, tax_code, transaction_date),
    INDEX idx_adjustment (tenant_id, original_transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Sample JSON Schema

**context column:**
```json
{
  "transaction_id": "INV-12345",
  "transaction_type": "customer_invoice",
  "transaction_date": "2024-11-24",
  "tax_code": "US-CA-SALES",
  "origin_address": {
    "country": "US",
    "state": "CA",
    "city": "San Francisco",
    "postal_code": "94102"
  },
  "destination_address": {
    "country": "US",
    "state": "CA",
    "city": "Los Angeles",
    "postal_code": "90001"
  },
  "customer_id": "CUST-001",
  "tax_jurisdiction": {
    "federal_code": "US",
    "state_code": "CA",
    "local_code": null
  },
  "exemption_certificate_id": null,
  "service_classification": null
}
```

**tax_breakdown column:**
```json
{
  "net_amount": {
    "amount": "100.00",
    "currency": "USD"
  },
  "total_tax_amount": {
    "amount": "7.25",
    "currency": "USD"
  },
  "gross_amount": {
    "amount": "107.25",
    "currency": "USD"
  },
  "tax_lines": [
    {
      "tax_code": "US-CA-SALES",
      "description": "California Sales Tax",
      "taxable_base": {
        "amount": "100.00",
        "currency": "USD"
      },
      "rate_percentage": "7.2500",
      "tax_amount": {
        "amount": "7.25",
        "currency": "USD"
      },
      "gl_account_code": "2210",
      "application_order": 1,
      "children": []
    }
  ],
  "is_reverse_charge": false
}
```

---

## Exemption Certificates Table

### Purpose
Store tax exemption certificates with partial exemption support.

### SQL DDL (PostgreSQL)

```sql
CREATE TABLE exemption_certificates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    
    -- Certificate Identification
    certificate_id VARCHAR(50) NOT NULL,
    customer_id VARCHAR(50) NOT NULL,
    
    -- Exemption Details
    exemption_reason VARCHAR(30) NOT NULL CHECK (exemption_reason IN ('resale', 'government', 'nonprofit', 'export', 'diplomatic', 'agricultural')),
    exemption_percentage DECIMAL(5, 4) NOT NULL CHECK (exemption_percentage >= 0 AND exemption_percentage <= 100),
    
    -- Validity Period
    issue_date DATE NOT NULL,
    expiration_date DATE,
    
    -- Document Storage
    storage_key VARCHAR(255), -- Reference to Nexus\Storage
    
    -- Metadata
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT uq_certificate_id UNIQUE (tenant_id, certificate_id),
    CONSTRAINT chk_expiration CHECK (expiration_date IS NULL OR expiration_date > issue_date)
);

-- Comments
COMMENT ON COLUMN exemption_certificates.exemption_percentage IS 'Partial exemption support (0.0000 = no exemption, 100.0000 = full exemption, 50.0000 = 50% exemption)';
COMMENT ON COLUMN exemption_certificates.storage_key IS 'Reference to PDF in Nexus\Storage (e.g., "exemption-certs/CERT-12345.pdf")';
```

---

## Nexus Thresholds Table

### Purpose
Define economic nexus revenue/transaction thresholds by jurisdiction.

### SQL DDL (PostgreSQL)

```sql
CREATE TABLE nexus_thresholds (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    
    -- Jurisdiction
    jurisdiction_code VARCHAR(50) NOT NULL,
    
    -- Thresholds (OR logic - either triggers nexus)
    revenue_threshold_amount DECIMAL(15, 4),
    revenue_threshold_currency CHAR(3),
    transaction_threshold INTEGER,
    
    -- Temporal Validity
    effective_from DATE NOT NULL,
    effective_to DATE,
    
    -- Metadata
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT chk_threshold_exists CHECK (
        revenue_threshold_amount IS NOT NULL OR transaction_threshold IS NOT NULL
    ),
    CONSTRAINT uq_nexus_temporal UNIQUE (tenant_id, jurisdiction_code, effective_from)
);

-- Sample Data
INSERT INTO nexus_thresholds (id, tenant_id, jurisdiction_code, revenue_threshold_amount, revenue_threshold_currency, transaction_threshold, effective_from)
VALUES 
    ('01HF7XZQK3YZ8PM2NV6TGQR9WG', '01HF7XZQK3YZ8PM2NV6TGQR9WA', 'US-CA', 100000.00, 'USD', 200, '2024-01-01'),
    ('01HF7XZQK3YZ8PM2NV6TGQR9WH', '01HF7XZQK3YZ8PM2NV6TGQR9WA', 'US-NY', 500000.00, 'USD', NULL, '2024-01-01');
```

---

## Indexes and Performance

### Critical Indexes

```sql
-- Tax Rates: Temporal Lookup (MOST CRITICAL)
CREATE INDEX idx_tax_rates_temporal_lookup 
ON tax_rates (tenant_id, tax_code, effective_from, effective_to);

-- Tax Rates: Jurisdiction Lookup
CREATE INDEX idx_tax_rates_jurisdiction 
ON tax_rates (tenant_id, jurisdiction_code, effective_from);

-- Tax Audit Log: Date Range Queries
CREATE INDEX idx_tax_audit_date_range 
ON tax_audit_log (tenant_id, transaction_date, tax_code);

-- Tax Audit Log: Transaction Lookup
CREATE INDEX idx_tax_audit_transaction 
ON tax_audit_log (transaction_id);

-- Tax Audit Log: Adjustment Tracing
CREATE INDEX idx_tax_audit_adjustment 
ON tax_audit_log (tenant_id, original_transaction_id) 
WHERE is_adjustment = TRUE;

-- Exemption Certificates: Customer Lookup
CREATE INDEX idx_exemption_customer 
ON exemption_certificates (tenant_id, customer_id);

-- Exemption Certificates: Expiration Monitoring
CREATE INDEX idx_exemption_expiration 
ON exemption_certificates (tenant_id, expiration_date) 
WHERE expiration_date IS NOT NULL;

-- Nexus Thresholds: Jurisdiction Lookup
CREATE INDEX idx_nexus_jurisdiction 
ON nexus_thresholds (tenant_id, jurisdiction_code, effective_from);
```

### Query Optimization Tips

**1. Always Include Tenant ID:**
```sql
-- ✅ CORRECT
SELECT * FROM tax_rates 
WHERE tenant_id = ? AND tax_code = ? AND effective_from <= ? ...

-- ❌ WRONG (no tenant_id)
SELECT * FROM tax_rates 
WHERE tax_code = ? ...
```

**2. Use Covering Indexes for Hot Queries:**
```sql
CREATE INDEX idx_tax_rates_covering 
ON tax_rates (tenant_id, tax_code, effective_from, effective_to)
INCLUDE (rate_percentage, gl_account_code, application_order);
```

**3. Partition Tax Audit Log by Date:**
```sql
-- PostgreSQL 12+ Table Partitioning
CREATE TABLE tax_audit_log (
    ...
) PARTITION BY RANGE (transaction_date);

CREATE TABLE tax_audit_log_2024_q1 PARTITION OF tax_audit_log
FOR VALUES FROM ('2024-01-01') TO ('2024-04-01');

CREATE TABLE tax_audit_log_2024_q2 PARTITION OF tax_audit_log
FOR VALUES FROM ('2024-04-01') TO ('2024-07-01');
```

---

## Retention Policies

### Compliance Requirements

**Tax Audit Log:**
- **US:** 7 years (IRS requirement)
- **EU:** 10 years (VAT directive)
- **Recommendation:** 10 years for global compliance

**Implementation:**

```sql
-- Archive old audit logs (PostgreSQL)
CREATE TABLE tax_audit_log_archive (LIKE tax_audit_log INCLUDING ALL);

-- Annual archival job
WITH archived AS (
    DELETE FROM tax_audit_log
    WHERE transaction_date < CURRENT_DATE - INTERVAL '10 years'
    RETURNING *
)
INSERT INTO tax_audit_log_archive SELECT * FROM archived;
```

**Tax Rates:**
- Retain indefinitely (needed for temporal queries)
- Archive closed rates (effective_to < 5 years ago) to separate table

**Exemption Certificates:**
- Retain 3 years after expiration (audit purposes)

---

## Database Migration Tools

### Laravel Migration Example

See `docs/MIGRATION.md` for complete backfill strategy.

### Liquibase Changeset Example

```xml
<changeSet id="create-tax-rates-table" author="nexus">
    <createTable tableName="tax_rates">
        <column name="id" type="uuid">
            <constraints primaryKey="true"/>
        </column>
        <column name="tenant_id" type="uuid">
            <constraints nullable="false"/>
        </column>
        <!-- ... other columns ... -->
    </createTable>
    
    <createIndex tableName="tax_rates" indexName="idx_tax_rates_temporal_lookup">
        <column name="tenant_id"/>
        <column name="tax_code"/>
        <column name="effective_from"/>
        <column name="effective_to"/>
    </createIndex>
</changeSet>
```

---

## Security Considerations

1. **Row-Level Security (PostgreSQL):**
```sql
ALTER TABLE tax_rates ENABLE ROW LEVEL SECURITY;

CREATE POLICY tax_rates_tenant_isolation ON tax_rates
FOR ALL
USING (tenant_id = current_setting('app.current_tenant')::uuid);
```

2. **Audit Log Immutability:**
```sql
-- Revoke UPDATE/DELETE permissions
REVOKE UPDATE, DELETE ON tax_audit_log FROM app_user;

-- Only INSERT and SELECT allowed
GRANT INSERT, SELECT ON tax_audit_log TO app_user;
```

3. **Encryption at Rest:**
- Use database-level encryption (AWS RDS, Azure SQL)
- Encrypt `context` and `tax_breakdown` columns (sensitive addresses)

---

**Related Documentation:**
- [Migration Guide](MIGRATION.md) - Temporal data backfill strategy
- [Integration Guide](integration-guide.md) - Repository implementations
- [Getting Started](getting-started.md) - Quick setup
