#!/usr/bin/env bash
set -euo pipefail

manifest="${MANIFEST:-docs/package-publishing/nexus-layer3-packages.manifest.json}"
execute=0
packagist_username="${PACKAGIST_USERNAME:-}"
packagist_token="${PACKAGIST_API_TOKEN:-}"
user_agent="${PACKAGIST_USER_AGENT:-Nexus package publishing automation mailto:117408+azaharizaman@users.noreply.github.com}"

if [[ "${1:-}" == "--execute" ]]; then
  execute=1
fi

if [[ ! -f "$manifest" ]]; then
  echo "Missing manifest: $manifest" >&2
  echo "Run: php tools/package-publishing/build-layer3-manifest.php" >&2
  exit 1
fi

if [[ "$execute" -eq 1 && ( -z "$packagist_username" || -z "$packagist_token" ) ]]; then
  echo "PACKAGIST_USERNAME and PACKAGIST_API_TOKEN are required for --execute" >&2
  exit 1
fi

jq -c '.packages[]' "$manifest" | while read -r package; do
  repo_url="$(jq -r '.github_url' <<<"$package")"
  package_name="$(jq -r '.new_name' <<<"$package")"

  if [[ "$execute" -eq 0 ]]; then
    echo "DRY RUN: create Packagist package $package_name from $repo_url"
    continue
  fi

  curl -fsS \
    -X POST \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer ${packagist_username}:${packagist_token}" \
    -H "User-Agent: ${user_agent}" \
    "https://packagist.org/api/create-package" \
    -d "{\"repository\":\"${repo_url}\"}"
  echo
done
