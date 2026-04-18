# Awards Lifecycle

This file describes the award state model itself. The operational create, split, debrief, protest, and signoff flows are documented in [workflows.md](./workflows.md).

## Purpose

The Awards lifecycle captures the final decision state of an RFQ outcome.

The controller and model layer together expose a small, explicit state machine that is driven by comparison-finalization, protest handling, and signoff.

## State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `pending` | The award has been created but is not yet signed off. | `AwardController::store()`, `AwardController::resolveProtest()` |
| `protested` | A protest is open on the award. | `AwardController::protest()` |
| `signed_off` | The award has been explicitly approved and signed off. | `AwardController::signoff()`, `AwardController::resolveProtest()` |

## Entry Criteria

### pending

- The award has just been created from finalized comparison evidence.
- No protest marker is open.

### protested

- The award has been protested and carries a protest ID.

### signed_off

- The award has been locked in by signoff.
- The signoff timestamp and signer are present.

## Transitions

### pending -> protested

- Trigger: a user opens a protest on the award.

### pending -> signed_off

- Trigger: a user signs off the award.

### protested -> pending

- Trigger: the protest is resolved before signoff.

### protested -> signed_off

- Trigger: the protest is resolved after signoff has already happened.

## State Rules

- Split-detail edits do not move the award between states.
- Debrief creation is an auxiliary record, not a state transition.
- The protest marker must match before a protest can be resolved.
- The controller treats already signed-off awards as stable and returns them as-is.

## Related Docs

- Award workflows: [workflows.md](./workflows.md)
- Award entities: [entities.md](./entities.md)
