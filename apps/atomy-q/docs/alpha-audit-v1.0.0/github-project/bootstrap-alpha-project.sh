#!/usr/bin/env bash
# Sync Alpha 30-day plan into GitHub Project (draft issues + custom fields).
# Requires: gh, jq
# Auth: gh auth refresh -s read:project -s project -h github.com
set -euo pipefail

OWNER="${ALPHA_PLAN_OWNER:-azaharizaman}"
PNUM="${ALPHA_PLAN_NUMBER:-4}"
DAY1="${ALPHA_DAY1_DATE:-2026-03-23}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ITEMS_JSON="${SCRIPT_DIR}/alpha-plan-items.json"

if ! command -v gh >/dev/null || ! command -v jq >/dev/null; then
  echo "Install gh and jq." >&2
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "Run: gh auth login" >&2
  exit 1
fi

echo "==> Resolving project id for ${OWNER} project #${PNUM}..."
PROJECT_JSON="$(gh project view "${PNUM}" --owner "${OWNER}" --format json)"
PROJECT_ID="$(echo "${PROJECT_JSON}" | jq -r .id)"
if [[ -z "${PROJECT_ID}" || "${PROJECT_ID}" == "null" ]]; then
  echo "Could not read project id. Grant scopes: gh auth refresh -s read:project -s project -h github.com" >&2
  exit 1
fi
echo "    project id: ${PROJECT_ID}"

ensure_field_single_select() {
  local name="$1"
  local options_csv="$2"
  local existing
  existing="$(gh project field-list "${PNUM}" --owner "${OWNER}" --format json | jq -r --arg n "$name" '.fields[] | select(.name == $n) | .id')"
  if [[ -n "${existing}" && "${existing}" != "null" ]]; then
    echo "    field exists: ${name} (${existing})"
    return
  fi
  echo "    creating field: ${name}"
  gh project field-create "${PNUM}" --owner "${OWNER}" --name "${name}" --data-type SINGLE_SELECT --single-select-options "${options_csv}" --format json >/dev/null
}

ensure_field_date() {
  local name="$1"
  local existing
  existing="$(gh project field-list "${PNUM}" --owner "${OWNER}" --format json | jq -r --arg n "$name" '.fields[] | select(.name == $n) | .id')"
  if [[ -n "${existing}" && "${existing}" != "null" ]]; then
    echo "    field exists: ${name} (${existing})"
    return
  fi
  echo "    creating field: ${name}"
  gh project field-create "${PNUM}" --owner "${OWNER}" --name "${name}" --data-type DATE --format json >/dev/null
}

ensure_field_number() {
  local name="$1"
  local existing
  existing="$(gh project field-list "${PNUM}" --owner "${OWNER}" --format json | jq -r --arg n "$name" '.fields[] | select(.name == $n) | .id')"
  if [[ -n "${existing}" && "${existing}" != "null" ]]; then
    echo "    field exists: ${name} (${existing})"
    return
  fi
  echo "    creating field: ${name}"
  gh project field-create "${PNUM}" --owner "${OWNER}" --name "${name}" --data-type NUMBER --format json >/dev/null
}

echo "==> Ensuring custom fields (safe to re-run)..."
ensure_field_number "Alpha day"
ensure_field_single_select "Alpha week" "W1,W2,W3,W4,W5"
ensure_field_single_select "Theme" "Foundation,Golden-path API,AI + Awards + mocks,Release candidate,Launch + comms"
ensure_field_single_select "Primary track" "Cross-functional,Engineering,Integration,Deployment,Infra,Marketing,IR,Product"
ensure_field_date "Target date"

echo "==> Refreshing field metadata..."
FIELD_LIST_JSON="$(gh project field-list "${PNUM}" --owner "${OWNER}" --format json)"

field_id() {
  local n="$1"
  echo "${FIELD_LIST_JSON}" | jq -r --arg n "$n" '.fields[] | select(.name == $n) | .id' | head -1
}

option_id_for_field() {
  local fname="$1"
  local oname="$2"
  echo "${FIELD_LIST_JSON}" | jq -r --arg fname "$fname" --arg oname "$oname" '
    .fields[] | select(.name == $fname) | .options[]? | select(.name == $oname) | .id
  ' | head -1
}

FID_DAY="$(field_id "Alpha day")"
FID_WEEK="$(field_id "Alpha week")"
FID_THEME="$(field_id "Theme")"
FID_TRACK="$(field_id "Primary track")"
FID_DATE="$(field_id "Target date")"

if [[ -z "${FID_DATE}" || "${FID_DATE}" == "null" ]]; then
  echo "Could not resolve field ids. Check field-list output." >&2
  exit 1
fi

# Default track for all items (editable in UI)
TRACK_CROSS="$(option_id_for_field "Primary track" "Cross-functional")"
if [[ -z "${TRACK_CROSS}" || "${TRACK_CROSS}" == "null" ]]; then
  echo "Warning: could not resolve 'Cross-functional' option id for Primary track." >&2
fi

map_week_option() {
  case "$1" in
    W1) echo "W1" ;;
    W2) echo "W2" ;;
    W3) echo "W3" ;;
    W4) echo "W4" ;;
    W5) echo "W5" ;;
    *) echo "W1" ;;
  esac
}

map_theme_option() {
  case "$1" in
    "Foundation") echo "Foundation" ;;
    "Golden-path API") echo "Golden-path API" ;;
    "AI + Awards + mocks") echo "AI + Awards + mocks" ;;
    "Release candidate") echo "Release candidate" ;;
    "Launch + comms") echo "Launch + comms" ;;
    *) echo "Foundation" ;;
  esac
}

echo "==> Creating draft items from ${ITEMS_JSON}..."
COUNT="$(jq length "${ITEMS_JSON}")"
for i in $(seq 0 $((COUNT - 1))); do
  TITLE="$(jq -r ".[$i].title" "${ITEMS_JSON}")"
  BODY="$(jq -r ".[$i].body" "${ITEMS_JSON}")"
  DAY="$(jq -r ".[$i].day" "${ITEMS_JSON}")"
  WEEK="$(jq -r ".[$i].week" "${ITEMS_JSON}")"
  THEME="$(jq -r ".[$i].theme" "${ITEMS_JSON}")"
  TDATE="$(jq -r ".[$i].target_date" "${ITEMS_JSON}")"

  echo "    creating: ${TITLE}"
  OUT=""
  ITEM_ID=""
  for attempt in 1 2 3; do
    OUT="$(gh project item-create "${PNUM}" --owner "${OWNER}" --title "${TITLE}" --body "${BODY}" --format json 2>&1)" || true
    ITEM_ID="$(echo "${OUT}" | jq -r .id 2>/dev/null)" || ITEM_ID=""
    if [[ -n "${ITEM_ID}" && "${ITEM_ID}" != "null" ]]; then
      break
    fi
    echo "    retry ${attempt} after API error..." >&2
    sleep 2
  done

  if [[ -z "${ITEM_ID}" || "${ITEM_ID}" == "null" ]]; then
    echo "Failed to create item or read id: ${OUT}" >&2
    exit 1
  fi

  # Set fields (best-effort; gh version differences may require UI edits)
  if [[ -n "${FID_DAY}" && "${FID_DAY}" != "null" ]]; then
    gh project item-edit --id "${ITEM_ID}" --project-id "${PROJECT_ID}" --field-id "${FID_DAY}" --number "${DAY}" 2>/dev/null || true
  fi
  if [[ -n "${FID_DATE}" && "${FID_DATE}" != "null" ]]; then
    gh project item-edit --id "${ITEM_ID}" --project-id "${PROJECT_ID}" --field-id "${FID_DATE}" --date "${TDATE}" 2>/dev/null || true
  fi
  WOPT="$(map_week_option "${WEEK}")"
  WID="$(option_id_for_field "Alpha week" "${WOPT}")"
  if [[ -n "${WID}" && "${WID}" != "null" ]]; then
    gh project item-edit --id "${ITEM_ID}" --project-id "${PROJECT_ID}" --field-id "${FID_WEEK}" --single-select-option-id "${WID}" 2>/dev/null || true
  fi
  TOPT="$(map_theme_option "${THEME}")"
  TID="$(option_id_for_field "Theme" "${TOPT}")"
  if [[ -n "${TID}" && "${TID}" != "null" ]]; then
    gh project item-edit --id "${ITEM_ID}" --project-id "${PROJECT_ID}" --field-id "${FID_THEME}" --single-select-option-id "${TID}" 2>/dev/null || true
  fi
  if [[ -n "${TRACK_CROSS}" && "${TRACK_CROSS}" != "null" ]]; then
    gh project item-edit --id "${ITEM_ID}" --project-id "${PROJECT_ID}" --field-id "${FID_TRACK}" --single-select-option-id "${TRACK_CROSS}" 2>/dev/null || true
  fi

  sleep 0.75
done

echo "==> Done. Open: https://github.com/users/${OWNER}/projects/${PNUM}"
echo "    Tip: Group board by 'Alpha week' or 'Theme'; filter by Target date."
echo "    Edit 'Primary track' per item for ownership."
