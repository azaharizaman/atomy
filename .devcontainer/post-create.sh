#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_DIR="$ROOT_DIR/apps/atomy-q/Design-System-v2"

if [[ -d "$APP_DIR" ]]; then
  echo "[post-create] Installing dependencies for Design-System-v2..."
  cd "$APP_DIR"
  npm install
else
  echo "[post-create] Skipped: $APP_DIR not found."
fi
