#!/bin/bash
set -e

echo "Starting SuiteCRM PowerPack..."

# Copy SuiteCRM files to volume if empty (first run)
if [ ! -f "/bitnami/suitecrm/public/index.php" ]; then
    echo "Copying SuiteCRM files to persistent volume..."
    cp -a /opt/bitnami/suitecrm/. /bitnami/suitecrm/
    
    # Create custom/Extension directory if it doesn't exist
    mkdir -p /bitnami/suitecrm/custom/Extension
    
    chown -R daemon:daemon /bitnami/suitecrm
    echo "Files copied successfully"
    
    # Mark that modules need installation after SuiteCRM setup
    touch /bitnami/suitecrm/.modules_pending
fi

# Fix session directory permissions
echo "Setting session directory permissions..."
chmod 777 /opt/bitnami/php/var/run/session 2>/dev/null || true
chown -R daemon:daemon /opt/bitnami/php/var/run/session 2>/dev/null || true

# Auto-install SuiteCRM if database credentials are provided and not yet installed
if [ ! -f "/bitnami/suitecrm/config.php" ] && [ -n "$SUITECRM_DATABASE_HOST" ] && [ "$SUITECRM_SKIP_INSTALL" != "yes" ]; then
    echo "Database configured - running silent installation..."
    
    # Wait for database to be ready (simple TCP check)
    echo "Waiting for database connection..."
    for i in {1..30}; do
        if timeout 2 bash -c "</dev/tcp/$SUITECRM_DATABASE_HOST/${SUITECRM_DATABASE_PORT_NUMBER:-3306}" 2>/dev/null; then
            echo "✅ Database is ready!"
            break
        fi
        echo "Waiting for database... ($i/30)"
        sleep 2
    done
    
    # Run silent installation
    if /opt/bitnami/scripts/suitecrm/silent-install.sh; then
        echo "✅ SuiteCRM installed successfully!"
        # Remove pending flag since we'll install modules immediately after
        rm -f /bitnami/suitecrm/.modules_pending
    else
        echo "⚠️  Silent installation failed. You can install manually at http://your-domain/install.php"
        echo "⚠️  Or use: docker exec <container> suitecrm:app:install"
    fi
fi

# Auto-install custom modules if SuiteCRM is installed and modules are pending
if [ -f "/bitnami/suitecrm/config.php" ] && [ -f "/bitnami/suitecrm/.modules_pending" ]; then
    echo "SuiteCRM is installed - installing custom modules automatically..."
    
    # Wait a moment for everything to settle
    sleep 3
    
    # Run module installation script
    if /opt/bitnami/scripts/suitecrm/install-modules.sh; then
        rm -f /bitnami/suitecrm/.modules_pending
        echo "✅ Custom modules installed successfully!"
        
        # Enable modules in SuiteCRM 8 interface
        echo "Enabling modules in SuiteCRM 8 interface..."
        /opt/bitnami/scripts/suitecrm/enable-modules-suite8.sh || echo "Module enablement had warnings, continuing..."
    else
        echo "⚠️  Module installation failed. Will retry on next start."
    fi
fi

# Disable HTTPS vhost since SSL is handled by reverse proxy
if [ -f "/opt/bitnami/apache/conf/vhosts/suitecrm-https-vhost.conf" ]; then
    echo "Disabling HTTPS vhost (SSL handled by reverse proxy)..."
    mv /opt/bitnami/apache/conf/vhosts/suitecrm-https-vhost.conf \
       /opt/bitnami/apache/conf/vhosts/suitecrm-https-vhost.conf.disabled
fi

# Update Apache DocumentRoot to use the persistent volume with correct public directory
sed -i 's|DocumentRoot "/opt/bitnami/suitecrm"|DocumentRoot "/bitnami/suitecrm/public"|g' /opt/bitnami/apache/conf/httpd.conf
sed -i 's|DocumentRoot /opt/bitnami/suitecrm|DocumentRoot /bitnami/suitecrm/public|g' /opt/bitnami/apache/conf/vhosts/*.conf 2>/dev/null || true
sed -i 's|<Directory "/opt/bitnami/suitecrm">|<Directory "/bitnami/suitecrm/public">|g' /opt/bitnami/apache/conf/vhosts/*.conf 2>/dev/null || true

# Set ServerName to localhost since Nginx reverse proxy handles domain routing
sed -i 's|ServerName www.example.com|ServerName localhost|g' /opt/bitnami/apache/conf/vhosts/*.conf 2>/dev/null || true

# Set required environment variables
export BITNAMI_APP_NAME="suitecrm"

# Start Apache
exec /opt/bitnami/scripts/apache/run.sh
