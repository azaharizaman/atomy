# Atomy-Q AI Provider Check Command Design

## Goal

Add an operator-facing Artisan command that checks the health and readiness of configured Atomy-Q AI provider endpoints without making paid or mutating provider calls by default.

The command complements the existing AI commands:

- `atomy:ai-status` remains the runtime status snapshot and optional alert publisher.
- `atomy:ai-verify-contracts` remains the representative provider contract smoke check.
- `atomy:ai-drill` remains the failure-mode runbook helper.
- The new command summarizes provider readiness for deploy gates, cron monitors, and operator diagnostics.

## Command Shape

```bash
php artisan atomy:ai-provider-check
php artisan atomy:ai-provider-check --endpoint-group=document
php artisan atomy:ai-provider-check --deep
php artisan atomy:ai-provider-check --json
php artisan atomy:ai-provider-check --fail-on=warning
php artisan atomy:ai-provider-check --publish-alerts
```

Options:

- `--endpoint-group=*`: Restrict checks to one or more AI endpoint groups.
- `--deep`: Run representative provider contract calls. This may consume provider quota.
- `--json`: Emit machine-readable JSON.
- `--fail-on=warning`: Exit non-zero when any warning is present.
- `--publish-alerts`: Publish degraded/unavailable alerts using the same snapshot semantics as `atomy:ai-status`.

Supported endpoint groups are the existing Atomy-Q groups: `document`, `normalization`, `sourcing_recommendation`, `comparison_award`, `insight`, and `governance`.

## Default Safe Checks

The default command is cheap and read-only. It must not send business generation prompts or consume provider completion quota.

It checks:

- AI mode: `off`, `deterministic`, or `provider`.
- Provider identity from configured provider key/name.
- Endpoint configuration presence, URI, enabled flag, timeout, retry attempts, retry backoff, model metadata, and health URL/method.
- Endpoint probe result using the configured health probe.
- Runtime capability status using the existing AI runtime status adapter.
- Operator configuration: AI operations log channel, alert cooldown, and configured alert recipients.
- Configuration risks:
  - provider mode with missing token;
  - provider mode with no configured endpoint URI;
  - plain HTTP endpoint outside local development hosts;
  - timeout below one second after normalization would be impossible, but very low configured timeout should warn;
  - retry attempts/backoff likely to amplify outage pressure;
  - missing model metadata where the endpoint group depends on a provider model.

The command must sanitize secrets. Tokens, request payloads, and customer content are never printed.

## Deep Checks

`--deep` explicitly permits provider calls that can consume quota. Deep checks reuse the representative contract verification behavior already present in the API app instead of duplicating sample payloads.

Deep checks verify:

- Each selected endpoint group accepts the representative request.
- The provider returns an associative JSON payload.
- Auth failures, timeouts, quota errors, retry exhaustion, unavailable providers, and invalid payloads are classified with operator-readable reason codes.
- Latency is captured where available.
- No synthetic success payload is accepted.

Deep checks must not create tenant business records, mutate workflow state, or persist AI artifacts.

## Severity Model

Each finding has one severity:

- `ok`: The check passed.
- `warning`: Operator attention is useful, but the command exits `0` by default.
- `failed`: Provider mode cannot be trusted for the checked capability. The command exits `1`.
- `skipped`: The check is intentionally not applicable, such as `off` or `deterministic` mode.
- `unknown`: The provider or configuration does not expose enough signal.

Exit behavior:

- Exit `0` when all findings are `ok`, `skipped`, `unknown`, or `warning`.
- Exit `1` when any finding is `failed`.
- Exit `1` for warnings when `--fail-on=warning` is used.
- Exit `1` for invalid command options.

## Output

Human output should be compact:

- Header: mode, provider, checked-at timestamp, global result.
- Endpoint table: endpoint group, configured state, probe health, latency, severity, reason codes.
- Operator findings table: severity, area, message.
- Deep section only when `--deep` is used.

JSON output includes:

- `checked_at`
- `mode`
- `provider`
- `global_status`
- `deep`
- `endpoint_groups`
- `operator_findings`
- `published_alerts` when requested
- `exit_severity`

## Architecture

The command belongs in `apps/atomy-q/API/app/Console/Commands`.

It should compose existing contracts where possible:

- `AiEndpointRegistryInterface` for mode, provider identity, endpoint groups, and endpoint config.
- `AiHealthProbeInterface` for safe endpoint probes.
- `AiRuntimeStatusInterface` for capability-level runtime status.
- `AiOperationalAlertPublisherInterface` for optional alert publication.

Introduce a small application service if the command logic grows beyond simple orchestration, for example `App\Services\Ai\AiProviderReadinessChecker`. The command should own console formatting, while the service returns structured check results.

Do not move Laravel HTTP/config concerns into L1 packages. Provider probing and operator diagnostics are adapter/application responsibilities for Atomy-Q.

## Provider-Side Signals Worth Checking

Provider integrations should make these signals visible when the provider supports them:

- Account authentication and authorization.
- Quota, credits, spend cap, or rate-limit exhaustion.
- Rate-limit reset and retry-after hints.
- Model availability, retirement, gating, and revision drift.
- Provider status page incident state through an optional configured URL.
- Latency trend against configured timeout.
- Response content type and JSON shape.
- Safety/refusal wrappers where business output is expected.

Provider-specific checks must degrade to `unknown` when the provider does not expose the signal.

## Future Extension

Add optional provider-status checking with a config value such as `AI_PROVIDER_STATUS_URL`. This should remain separate from endpoint health so operators can distinguish a provider-wide incident from local credential or endpoint misconfiguration.

Add a readiness score only after the first command version has real operator usage. The first version should keep the result explainable through explicit findings and reason codes.

## Testing

Feature tests should cover:

- Safe default does not call contract clients.
- Provider mode with missing endpoint config reports failed/warning findings.
- `off` and `deterministic` modes mark provider endpoint checks as skipped.
- `--endpoint-group` filters checks and rejects unsupported groups.
- `--json` emits stable machine-readable keys.
- `--fail-on=warning` changes exit behavior.
- `--publish-alerts` delegates to the alert publisher.
- `--deep` invokes the representative contract checks and classifies failures.

Unit tests should cover any result aggregation service, especially severity rollup and secret sanitization.
