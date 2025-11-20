#!/bin/bash
set -e

echo "Running SuiteCRM silent installation..."

cd /bitnami/suitecrm

# Required environment variables
DB_HOST="${SUITECRM_DATABASE_HOST}"
DB_PORT="${SUITECRM_DATABASE_PORT_NUMBER:-3306}"
DB_NAME="${SUITECRM_DATABASE_NAME}"
DB_USER="${SUITECRM_DATABASE_USER}"
DB_PASSWORD="${SUITECRM_DATABASE_PASSWORD}"
SITE_URL="${SUITECRM_SITE_URL:-http://localhost}"
ADMIN_USER="${SUITECRM_USERNAME:-admin}"
ADMIN_PASSWORD="${SUITECRM_PASSWORD:-admin}"

# Validate required variables
if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASSWORD" ]; then
    echo "ERROR: Missing required database environment variables"
    echo "Required: SUITECRM_DATABASE_HOST, SUITECRM_DATABASE_NAME, SUITECRM_DATABASE_USER, SUITECRM_DATABASE_PASSWORD"
    exit 1
fi

# Create config_si.php for silent installation in legacy directory where install.php expects it
cat > /bitnami/suitecrm/public/legacy/config_si.php <<EOF
<?php
\$sugar_config_si = array(
    'setup_db_host_name' => '${DB_HOST}',
    'setup_db_port_num' => '${DB_PORT}',
    'setup_db_database_name' => '${DB_NAME}',
    'setup_db_admin_user_name' => '${DB_USER}',
    'setup_db_admin_password' => '${DB_PASSWORD}',
    'setup_db_type' => 'mysql',
    'setup_db_pop_demo_data' => false,
    'setup_db_create_database' => false,
    'setup_db_drop_tables' => false,
    'setup_db_username_is_privileged' => true,
    'demoData' => 'no',
    'setup_site_url' => '${SITE_URL}',
    'setup_site_admin_user_name' => '${ADMIN_USER}',
    'setup_site_admin_password' => '${ADMIN_PASSWORD}',
    'setup_site_admin_user_name_confirm' => '${ADMIN_USER}',
    'setup_site_admin_password_confirm' => '${ADMIN_PASSWORD}',
    'setup_system_name' => 'SuiteCRM PowerPack',
    'default_currency_iso4217' => 'USD',
    'default_currency_name' => 'US Dollar',
    'default_currency_significant_digits' => '2',
    'default_currency_symbol' => '\$',
    'default_date_format' => 'Y-m-d',
    'default_time_format' => 'H:i',
    'default_decimal_seperator' => '.',
    'default_number_grouping_seperator' => ',',
    'default_language' => 'en_us',
    'export_delimiter' => ',',
    'setup_license_key_users' => '0',
    'setup_license_key_expire_date' => '',
    'setup_license_key' => '',
    'setup_num_lic_oc' => '0',
);
?>
EOF

chown daemon:daemon /bitnami/suitecrm/public/legacy/config_si.php

echo "Running silent installer..."

# Create a PHP script to properly trigger the silent installation
cat > /tmp/run_silent_install.php <<'PHPEOF'
<?php
// Start session to avoid redirect loop
session_start();

// Set up environment to look like web request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '8080';
$_SERVER['REQUEST_URI'] = '/legacy/install.php?goto=SilentInstall&cli=true';
$_SERVER['SCRIPT_NAME'] = '/legacy/install.php';
$_SERVER['SCRIPT_FILENAME'] = '/bitnami/suitecrm/public/legacy/install.php';
$_SERVER['PHP_SELF'] = '/legacy/install.php';
$_GET['goto'] = 'SilentInstall';
$_GET['cli'] = 'true';

// Set the next_step to skip the redirect check
$_SESSION['setup_site_admin_user_name'] = '';

chdir('/bitnami/suitecrm/public/legacy');

// Capture output buffer to prevent HTML output
ob_start();
include('/bitnami/suitecrm/public/legacy/install.php');
$output = ob_get_clean();

// Only show errors, not the HTML
if (strpos($output, 'Fatal error') !== false || strpos($output, 'Warning') !== false) {
    echo $output;
}

// Check if installation succeeded
if (file_exists('/bitnami/suitecrm/config.php')) {
    echo "Installation completed successfully\n";
    exit(0);
} else {
    echo "Installation may have failed - config.php not found\n";
    echo $output;
    exit(1);
}
?>
PHPEOF

# Run the PHP script as daemon user
echo "Executing silent installation..."
OUTPUT=$(su -s /bin/bash daemon -c "cd /bitnami/suitecrm/public/legacy && php /tmp/run_silent_install.php 2>&1")
INSTALL_STATUS=$?

echo "$OUTPUT"

# Clean up
rm -f /tmp/run_silent_install.php

# Wait a moment for any async operations
sleep 3

if [ -f "/bitnami/suitecrm/config.php" ]; then
    echo "✅ SuiteCRM silent installation completed successfully!"
    
    # Clear cache and sessions
    echo "Clearing cache and sessions..."
    rm -rf /bitnami/suitecrm/cache/* 2>/dev/null || true
    rm -rf /bitnami/suitecrm/public/legacy/cache/* 2>/dev/null || true
    find /bitnami/suitecrm -type d -name "cache" -exec rm -rf {}/* \; 2>/dev/null || true
    
    # Apply Twilio configuration from environment
    if [ -f "/bitnami/suitecrm/config_override.php.template" ]; then
        echo "Applying Twilio configuration..."
        cp /bitnami/suitecrm/config_override.php.template /bitnami/suitecrm/config_override.php
        chown daemon:daemon /bitnami/suitecrm/config_override.php
    fi
    
    # Set permissions
    chown -R daemon:daemon /bitnami/suitecrm
    chmod -R 755 /bitnami/suitecrm
    
    # Clean up silent install config
    rm -f /bitnami/suitecrm/public/legacy/config_si.php
    
    exit 0
else
    echo "❌ SuiteCRM installation failed"
    echo "Installation output:"
    echo "$OUTPUT"
    exit 1
fi
