#!/usr/bin/env bash
set -euo pipefail

manifest="${MANIFEST:-docs/package-publishing/nexus-layer3-packages.manifest.json}"
execute=0
private="${PRIVATE_REPOS:-1}"
description_prefix="${DESCRIPTION_PREFIX:-Nexus layer-3 package}"

if [[ "${1:-}" == "--execute" ]]; then
  execute=1
fi

if [[ ! -f "$manifest" ]]; then
  echo "Missing manifest: $manifest" >&2
  echo "Run: php tools/package-publishing/build-layer3-manifest.php" >&2
  exit 1
fi

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI (gh) is required." >&2
  exit 1
fi

jq -c '.packages[]' "$manifest" | while read -r package; do
  owner="$(jq -r '.github_owner' <<<"$package")"
  repo="$(jq -r '.github_repo' <<<"$package")"
  description="$(jq -r '.description // empty' <<<"$package")"
  visibility_flag="--private"

  if [[ "$private" != "1" ]]; then
    visibility_flag="--public"
  fi

  if [[ -z "$description" ]]; then
    description="${description_prefix}: ${repo}"
  fi

  if [[ "$execute" -eq 0 ]]; then
    echo "DRY RUN: gh repo create ${owner}/${repo} ${visibility_flag} --description \"$description\""
    continue
  fi

  gh repo create "${owner}/${repo}" ${visibility_flag} --description "$description" >/dev/null
  echo "Created ${owner}/${repo}"
done
