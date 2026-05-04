#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
from_ref="${FROM_REF:-origin/main}"
to_ref="${TO_REF:-HEAD}"
execute=0
include_uncommitted=0
remote_protocol="${REMOTE_PROTOCOL:-ssh}"
release_tag="${RELEASE_TAG:-}"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --execute)
      execute=1
      shift
      ;;
    --from)
      from_ref="$2"
      shift 2
      ;;
    --to)
      to_ref="$2"
      shift 2
      ;;
    --include-uncommitted)
      include_uncommitted=1
      shift
      ;;
    *)
      echo "Unknown argument: $1" >&2
      exit 1
      ;;
  esac
done

if [[ "$remote_protocol" != "ssh" && "$remote_protocol" != "https" ]]; then
  echo "REMOTE_PROTOCOL must be ssh or https" >&2
  exit 1
fi

cd "$root_dir"

changed_file_list="$(mktemp)"
tmp_files=("$changed_file_list")

cleanup() {
  rm -f "${tmp_files[@]}"
}
trap cleanup EXIT

git diff --name-only "$from_ref" "$to_ref" | sed '/^$/d' | sort -u > "$changed_file_list"

if [[ "$include_uncommitted" -eq 1 ]]; then
  {
    git diff --name-only
    git diff --name-only --cached
  } | sed '/^$/d' | sort -u >> "$changed_file_list"
  sort -u "$changed_file_list" -o "$changed_file_list"
fi

if [[ ! -s "$changed_file_list" ]]; then
  echo "No changed files detected for range $from_ref..$to_ref"
  exit 0
fi

ensure_manifest() {
  local manifest="$1"
  local builder="$2"
  if [[ ! -f "$manifest" ]]; then
    php "$builder"
  fi
}

build_filtered_manifest() {
  local source_manifest="$1"
  local out_manifest="$2"
  local matched_paths_file="$3"

  : > "$matched_paths_file"

  while IFS= read -r path; do
    if grep -Eq "^${path}(/|$)" "$changed_file_list"; then
      echo "$path" >> "$matched_paths_file"
    fi
  done < <(jq -r '.packages[].path' "$source_manifest")

  if [[ ! -s "$matched_paths_file" ]]; then
    return 1
  fi

  jq --rawfile keep "$matched_paths_file" '
    def keep_paths: ($keep | split("\n") | map(select(length > 0)));
    .packages |= map(select(.path as $p | (keep_paths | index($p))))
    | .package_count = (.packages | length)
  ' "$source_manifest" > "$out_manifest"
}

run_layer() {
  local label="$1"
  local source_manifest="$2"
  local builder="$3"
  local split_script="$4"

  ensure_manifest "$source_manifest" "$builder"

  local filtered_manifest
  local matched_paths
  filtered_manifest="$(mktemp)"
  matched_paths="$(mktemp)"
  tmp_files+=("$filtered_manifest" "$matched_paths")

  if ! build_filtered_manifest "$source_manifest" "$filtered_manifest" "$matched_paths"; then
    echo "[$label] no changed packages"
    return 0
  fi

  local count
  count="$(jq -r '.package_count' "$filtered_manifest")"
  echo "[$label] changed packages: $count"
  jq -r '.packages[].path' "$filtered_manifest" | sed "s/^/[$label] - /"

  if [[ "$execute" -eq 1 ]]; then
    MANIFEST="$filtered_manifest" REMOTE_PROTOCOL="$remote_protocol" RELEASE_TAG="$release_tag" "$split_script" --execute
  else
    MANIFEST="$filtered_manifest" REMOTE_PROTOCOL="$remote_protocol" RELEASE_TAG="$release_tag" "$split_script"
  fi
}

run_layer \
  "L1" \
  "docs/package-publishing/nexus-layer1-packages.manifest.json" \
  "tools/package-publishing/build-layer1-manifest.php" \
  "tools/package-publishing/split-push-layer1-packages.sh"

run_layer \
  "L2" \
  "docs/package-publishing/nexus-layer2-packages.manifest.json" \
  "tools/package-publishing/build-layer2-manifest.php" \
  "tools/package-publishing/split-push-layer2-packages.sh"

run_layer \
  "L3" \
  "docs/package-publishing/nexus-layer3-packages.manifest.json" \
  "tools/package-publishing/build-layer3-manifest.php" \
  "tools/package-publishing/split-push-layer3-packages.sh"
