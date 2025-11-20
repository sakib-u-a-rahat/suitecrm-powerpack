#!/bin/bash
set -e

echo "Starting SuiteCRM PowerPack (skipping auto-install)..."

# Copy SuiteCRM files to volume if empty (first run)
if [ ! -f "/bitnami/suitecrm/public/index.php" ]; then
    echo "Copying SuiteCRM files to persistent volume..."
    cp -a /opt/bitnami/suitecrm/. /bitnami/suitecrm/
    chown -R daemon:daemon /bitnami/suitecrm
    echo "Files copied successfully"
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
