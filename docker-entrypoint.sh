#!/bin/bash
set -e

echo "Starting SuiteCRM with extended functionalities..."

# Set default port if not provided
DB_PORT="${SUITECRM_DATABASE_PORT:-3306}"

# Wait for database to be ready
echo "Connecting to database at ${SUITECRM_DATABASE_HOST}:${DB_PORT}..."
until mysql -h"$SUITECRM_DATABASE_HOST" -P"$DB_PORT" -u"$SUITECRM_DATABASE_USER" -p"$SUITECRM_DATABASE_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; do
    echo "Waiting for database connection..."
    sleep 3
done

echo "Database is ready!"

# Set permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Install custom modules if not already installed
if [ ! -f "/var/www/html/.modules_installed" ]; then
    echo "Installing custom modules..."
    /usr/local/bin/install-modules.sh
    touch /var/www/html/.modules_installed
fi

# Start cron service
service cron start

# Execute the main container command
exec "$@"
