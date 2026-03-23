# Atomy-Q Alpha plan → GitHub Project #4

**Project URL:** https://github.com/users/azaharizaman/projects/4  
**Source docs:** [`PLAN-WEEK-1.md`](../PLAN-WEEK-1.md) … [`PLAN-WEEK-5.md`](../PLAN-WEEK-5.md)

## Prerequisites

1. **GitHub CLI** (`gh`) installed and logged in.
2. **Project scopes** on your token (required for `gh project`):

   ```bash
   gh auth refresh -s read:project -s project -h github.com
   ```

3. Verify:

   ```bash
   gh auth status
   gh project field-list 4 --owner azaharizaman --format json | head
   ```

## What the script does

1. **Creates custom fields** on project `4` (if missing), using GitHub Projects single-select and date fields:
   - **Alpha Week** — `W1` … `W5`
   - **Theme** — matches weekly themes from the plan
   - **Primary track** — default `Cross-functional` (edit per item in UI for ownership)
   - **Target date** — calendar day for “Day 1 = start date” (default **2026-03-23**)

2. **Creates 30 draft issues** (one per Alpha day) with titles and bodies summarizing Engineering + Integration + Deployment + Infra + Marketing + IR actions.

3. **Sets field values** via `gh project item-edit` (requires `project-id` + `field-id`; script resolves them with `gh project field-list` / `jq`).

## Configuration

| Env | Default | Meaning |
|-----|---------|---------|
| `ALPHA_PLAN_OWNER` | `azaharizaman` | Project owner login |
| `ALPHA_PLAN_NUMBER` | `4` | Project number in URL |
| `ALPHA_DAY1_DATE` | `2026-03-23` | Calendar date for Alpha Day 1 |

Example:

```bash
export ALPHA_DAY1_DATE=2026-03-24   # if your “today” differs
./bootstrap-alpha-project.sh
```

## Files

| File | Purpose |
|------|---------|
| [`alpha-plan-items.json`](./alpha-plan-items.json) | Machine-readable items (day, week, theme, title, body) |
| [`bootstrap-alpha-project.sh`](./bootstrap-alpha-project.sh) | Executable: create fields + draft items + set fields |

## Visualization tips (GitHub Projects)

- **Group by** `Alpha Week` or `Theme`.
- **Filter** by date range using **Target date**.
- After import, assign **Primary track** (or use native **Assignees**) for real ownership.
- Link draft issues to **repo issues** later (`Convert to issue`) if you want them in `atomy`.

## Troubleshooting

- **`missing required scopes [read:project]`** — run `gh auth refresh` command above.
- **`field already exists`** — safe to ignore; script continues.
- **Rate limits** — script sleeps 0.3s between API calls; re-run is mostly idempotent (duplicate draft titles may still be created; delete duplicates in UI if needed).
