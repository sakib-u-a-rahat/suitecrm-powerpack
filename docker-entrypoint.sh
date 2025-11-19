#!/bin/bash
set -e

echo "Starting SuiteCRM PowerPack (skipping auto-install)..."

# Set required environment variables
export BITNAMI_APP_NAME="suitecrm"

# Start Apache directly without running Bitnami's setup wizard
exec /opt/bitnami/scripts/apache/run.sh
