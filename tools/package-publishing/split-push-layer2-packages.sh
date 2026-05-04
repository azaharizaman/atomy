#!/usr/bin/env bash
set -euo pipefail

manifest="${MANIFEST:-docs/package-publishing/nexus-layer2-packages.manifest.json}"
execute=0
tag="${RELEASE_TAG:-}"
remote_protocol="${REMOTE_PROTOCOL:-ssh}"

if [[ "${1:-}" == "--execute" ]]; then
  execute=1
fi

if [[ ! -f "$manifest" ]]; then
  echo "Missing manifest: $manifest" >&2
  echo "Run: php tools/package-publishing/build-layer2-manifest.php" >&2
  exit 1
fi

if [[ "$remote_protocol" != "ssh" && "$remote_protocol" != "https" ]]; then
  echo "REMOTE_PROTOCOL must be ssh or https" >&2
  exit 1
fi

jq -c '.packages[]' "$manifest" | while read -r package; do
  path="$(jq -r '.path' <<<"$package")"
  owner="$(jq -r '.github_owner' <<<"$package")"
  repo="$(jq -r '.github_repo' <<<"$package")"

  if [[ "$remote_protocol" == "ssh" ]]; then
    remote="git@github.com:${owner}/${repo}.git"
  else
    remote="https://github.com/${owner}/${repo}.git"
  fi

  if [[ "$execute" -eq 0 ]]; then
    echo "DRY RUN: git subtree split --prefix=$path HEAD"
    echo "DRY RUN: git push $remote <split-sha>:refs/heads/main"
    if [[ -n "$tag" ]]; then
      echo "DRY RUN: git push $remote <split-sha>:refs/tags/$tag"
    fi
    continue
  fi

  split_sha="$(git subtree split --prefix="$path" HEAD)"
  git push "$remote" "+${split_sha}:refs/heads/main"

  if [[ -n "$tag" ]]; then
    git push "$remote" "+${split_sha}:refs/tags/${tag}"
  fi
done
