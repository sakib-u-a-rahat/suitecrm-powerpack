#!/bin/bash
set -e

echo "Starting SuiteCRM PowerPack..."

# Ensure proper permissions (suppress errors as Bitnami may own these already)
chown -R 1001:1001 /bitnami/suitecrm 2>/dev/null || true
chmod -R 755 /bitnami/suitecrm 2>/dev/null || true

# Install custom modules only if SuiteCRM is installed
if [ -f "/bitnami/suitecrm/config.php" ] && [ ! -f "/bitnami/suitecrm/.modules_installed" ]; then
    echo "Installing PowerPack custom modules..."
    /opt/bitnami/scripts/suitecrm/install-modules.sh 2>&1 || echo "Module installation had warnings, continuing..."
    touch /bitnami/suitecrm/.modules_installed
    echo "Custom modules installed"
fi

# Execute Bitnami's original entrypoint (let it handle all variable initialization)
exec /opt/bitnami/scripts/suitecrm/entrypoint.sh "$@"
