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
- is auto-discovered from `sample/*/metadata.json` by the WEB fixture loader

Root-level PDFs are allowed for ad-hoc smoke tests, but release e2e should use folder fixtures with `metadata.json`.

WEB support commands:

- `cd apps/atomy-q/WEB && npm run test:unit:provider-quote-fixtures`
- `cd apps/atomy-q/WEB && npm run test:e2e:provider-quote:fake`
- `cd apps/atomy-q/WEB && AI_PROVIDER_E2E=true npm run test:e2e:provider-quote:live`

Live-provider defaults:

- `npm run test:e2e:provider-quote:live` runs one discovered requisition fixture by default.
- Set `PROVIDER_QUOTE_FIXTURE=<requisition_id>` to target a specific folder under `sample/`.

Cross-platform examples:

- POSIX shell:
  - `cd apps/atomy-q/WEB && PROVIDER_QUOTE_FIXTURE=aircond-compressor-repair-rfq-sibu-2019 AI_PROVIDER_E2E=true npm run test:e2e:provider-quote:live`
- `npm` script with bundled `cross-env`:
  - `cd apps/atomy-q/WEB && npm run test:e2e:provider-quote:live -- --grep "aircond-compressor-repair-rfq-sibu-2019"`
- Windows PowerShell:
  - `cd apps/atomy-q/WEB; $env:AI_PROVIDER_E2E='true'; $env:PROVIDER_QUOTE_FIXTURE='aircond-compressor-repair-rfq-sibu-2019'; npm run test:e2e:provider-quote:live`
- Windows CMD:
  - `cd apps/atomy-q/WEB && set AI_PROVIDER_E2E=true && set PROVIDER_QUOTE_FIXTURE=aircond-compressor-repair-rfq-sibu-2019 && npm run test:e2e:provider-quote:live`
