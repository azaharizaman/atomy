# Atomy-Q Provider Quote Samples

Use one folder per requisition/RFQ.

```text
sample/
  vehicle-service-rfq/
    metadata.json
    kuching-utama.pdf
    alternate-vendor.pdf
```

`metadata.json` drives provider-backed quote e2e:

- seeds RFQ header and line items
- uploads each vendor PDF
- checks provider extraction anchors
- verifies normalization/comparison readiness path

Root-level PDFs are allowed for ad-hoc smoke tests, but release e2e should use folder fixtures with `metadata.json`.

