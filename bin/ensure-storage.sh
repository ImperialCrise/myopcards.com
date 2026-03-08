#!/bin/bash
# Ensure storage directories exist with correct permissions for web server
set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_DIR="$(dirname "$SCRIPT_DIR")"

mkdir -p "$BASE_DIR/storage/cache"
mkdir -p "$BASE_DIR/storage/logs"
chmod -R 775 "$BASE_DIR/storage"
echo "Storage directories ensured: storage/cache, storage/logs"
