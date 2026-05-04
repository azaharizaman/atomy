#!/usr/bin/env bash
set -euo pipefail

manifest="${MANIFEST:-docs/package-publishing/nexus-layer2-packages.manifest.json}"
execute=0
visibility="${GITHUB_VISIBILITY:-public}"

if [[ "${1:-}" == "--execute" ]]; then
  execute=1
fi

if [[ ! -f "$manifest" ]]; then
  echo "Missing manifest: $manifest" >&2
  echo "Run: php tools/package-publishing/build-layer2-manifest.php" >&2
  exit 1
fi

if [[ "$visibility" != "public" && "$visibility" != "private" ]]; then
  echo "GITHUB_VISIBILITY must be public or private" >&2
  exit 1
fi

jq -c '.packages[]' "$manifest" | while read -r package; do
  owner="$(jq -r '.github_owner' <<<"$package")"
  repo="$(jq -r '.github_repo' <<<"$package")"
  description="$(jq -r '.description // ""' <<<"$package")"
  full_name="$owner/$repo"

  if [[ "$execute" -eq 0 ]]; then
    echo "DRY RUN: gh repo create $full_name --$visibility --description \"$description\""
    continue
  fi

  if gh repo view "$full_name" >/dev/null 2>&1; then
    echo "SKIP: $full_name already exists"
    continue
  fi

  gh repo create "$full_name" "--$visibility" --description "$description" --disable-wiki
done
