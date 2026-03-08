#!/bin/bash
# Generate secure MinIO keys with OpenSSL
# Usage: ./bin/generate-minio-keys.sh
# Add --update-env to automatically update .env file

ACCESS_KEY=$(openssl rand -hex 16)
SECRET_KEY=$(openssl rand -hex 16)

echo "Generated MinIO keys (add to .env):"
echo ""
echo "MINIO_ACCESS_KEY=$ACCESS_KEY"
echo "MINIO_SECRET_KEY=$SECRET_KEY"
echo ""

if [[ "$1" == "--update-env" ]]; then
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    ENV_FILE="$(dirname "$SCRIPT_DIR")/.env"
    if [[ -f "$ENV_FILE" ]]; then
        if grep -q "^MINIO_ACCESS_KEY=" "$ENV_FILE"; then
            sed -i.bak "s|^MINIO_ACCESS_KEY=.*|MINIO_ACCESS_KEY=$ACCESS_KEY|" "$ENV_FILE"
        else
            echo "" >> "$ENV_FILE"
            echo "MINIO_ACCESS_KEY=$ACCESS_KEY" >> "$ENV_FILE"
        fi
        if grep -q "^MINIO_SECRET_KEY=" "$ENV_FILE"; then
            sed -i.bak "s|^MINIO_SECRET_KEY=.*|MINIO_SECRET_KEY=$SECRET_KEY|" "$ENV_FILE"
        else
            echo "MINIO_SECRET_KEY=$SECRET_KEY" >> "$ENV_FILE"
        fi
        rm -f "${ENV_FILE}.bak" 2>/dev/null
        echo "Updated $ENV_FILE"
    else
        echo "Error: .env not found at $ENV_FILE"
        exit 1
    fi
fi
