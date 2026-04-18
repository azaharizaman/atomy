# Vendors Workflows

This file covers the operational business flow for the Vendors domain. It does not describe vendor state semantics; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- All routes are tenant-scoped.
- The controller uses exact tenant filtering instead of request-body tenant hints.

## Workflow 001 - List And Search Vendors

### Trigger

A buyer opens the vendor list or applies search filters.

### Steps

1. The API reads the tenant ID from the auth context.
2. The controller filters vendors by tenant, optional status, and optional search text.
3. The controller paginates the result set and serializes the vendor rows.

### Outputs

- Paginated vendor table
- Tenant-scoped search results

### Failure Handling

- Missing tenant context is a hard failure.

## Workflow 002 - View A Vendor Profile

### Trigger

A buyer opens a vendor detail page.

### Steps

1. The API looks up the vendor in the current tenant.
2. The controller serializes the core vendor profile fields.

### Outputs

- Vendor detail payload

### Failure Handling

- Missing vendors return `404`.

## Workflow 003 - Review Vendor Performance

### Trigger

A buyer opens the vendor performance tab.

### Steps

1. The API aggregates quote submissions for the vendor in the current tenant.
2. The controller counts submitted quotes, ready quotes, and awards won.
3. The controller calculates a score from the readiness ratio plus award contribution.

### Outputs

- Performance score
- Quote and award metric breakdown

### Failure Handling

- Missing vendors return `404`.
- Zero submissions produce a zero score instead of an error.

## Workflow 004 - Review Vendor Compliance And History

### Trigger

A buyer opens the compliance or history tabs for a vendor.

### Steps

1. The API resolves the vendor in the current tenant.
2. The compliance endpoint derives status from metadata or the vendor status string.
3. The history endpoint loads recent signed-off awards for that vendor.

### Outputs

- Compliance snapshot
- Recent award history

### Failure Handling

- Missing vendors return `404`.

## Domains Involved

- Vendors
- RFQ
- Awards
- Quote Intake
