#!/bin/bash
set -e

echo "============================================"
echo "Installing SuiteCRM PowerPack Modules"
echo "============================================"

# Verify we're in the right place
if [ ! -f "/bitnami/suitecrm/public/legacy/config.php" ]; then
    echo "ERROR: SuiteCRM config.php not found. Is SuiteCRM installed?"
    echo "Expected: /bitnami/suitecrm/public/legacy/config.php"
    exit 1
fi

# Verify modules exist
for MODULE in TwilioIntegration LeadJourney FunnelDashboard; do
    if [ ! -f "/bitnami/suitecrm/modules/$MODULE/$MODULE.php" ]; then
        echo "ERROR: Module $MODULE not found at /bitnami/suitecrm/modules/$MODULE/"
        exit 1
    fi
    echo "✓ Found module: $MODULE"
done

# Change to SuiteCRM directory
cd /bitnami/suitecrm/public/legacy || exit 1

echo ""
echo "Creating custom directories..."
mkdir -p /bitnami/suitecrm/public/legacy/custom/Extension/application/Ext/Include
mkdir -p /bitnami/suitecrm/public/legacy/custom/application/Ext/Include
mkdir -p /bitnami/suitecrm/public/legacy/custom/Extension/modules
mkdir -p /bitnami/suitecrm/public/legacy/custom/modules

# Copy module files to legacy directory
echo "Copying module files to legacy directory..."
for MODULE in TwilioIntegration LeadJourney FunnelDashboard; do
    if [ -d "/bitnami/suitecrm/modules/$MODULE" ]; then
        echo "  Copying $MODULE..."
        cp -r "/bitnami/suitecrm/modules/$MODULE" "/bitnami/suitecrm/public/legacy/modules/"
        chown -R daemon:daemon "/bitnami/suitecrm/public/legacy/modules/$MODULE"
    fi
done

# Copy extension files for modules
echo "Installing module extensions..."
mkdir -p /bitnami/suitecrm/public/legacy/custom/Extension/application/Ext/Language

if [ -d "/bitnami/suitecrm/modules/LeadJourney/Extensions" ]; then
    cp -r /bitnami/suitecrm/modules/LeadJourney/Extensions/* /bitnami/suitecrm/public/legacy/custom/Extension/ 2>/dev/null || true
fi

if [ -d "/bitnami/suitecrm/modules/TwilioIntegration/Extensions" ]; then
    cp -r /bitnami/suitecrm/modules/TwilioIntegration/Extensions/* /bitnami/suitecrm/public/legacy/custom/Extension/ 2>/dev/null || true
fi

# Create compiled language extension for dropdown lists
mkdir -p /bitnami/suitecrm/public/legacy/custom/application/Ext/Language
cat > /bitnami/suitecrm/public/legacy/custom/application/Ext/Language/en_us.lang.ext.php << 'PHPEOF'
<?php
// Touchpoint types for Lead Journey module
$app_list_strings['touchpoint_type_list'] = array(
    '' => '',
    'call' => 'Call',
    'email' => 'Email',
    'meeting' => 'Meeting',
    'site_visit' => 'Site Visit',
    'linkedin_click' => 'LinkedIn Click',
    'campaign' => 'Campaign',
    'form_submission' => 'Form Submission',
    'download' => 'Download',
    'webinar' => 'Webinar',
    'trade_show' => 'Trade Show',
    'referral' => 'Referral',
    'other' => 'Other',
);

// Parent type options for Lead Journey
$app_list_strings['lead_journey_parent_type_list'] = array(
    'Leads' => 'Lead',
    'Contacts' => 'Contact',
    'Accounts' => 'Account',
    'Opportunities' => 'Opportunity',
);
PHPEOF

# Create module registration files directly
echo ""
echo "Registering modules in SuiteCRM..."

# Create the source extension file
cat > /bitnami/suitecrm/public/legacy/custom/Extension/application/Ext/Include/powerpack_modules.php << 'PHPEOF'
<?php
// PowerPack Module Registration
$beanList['TwilioIntegration'] = 'TwilioIntegration';
$beanFiles['TwilioIntegration'] = 'modules/TwilioIntegration/TwilioIntegration.php';
$moduleList[] = 'TwilioIntegration';

$beanList['LeadJourney'] = 'LeadJourney';
$beanFiles['LeadJourney'] = 'modules/LeadJourney/LeadJourney.php';
$moduleList[] = 'LeadJourney';

$beanList['FunnelDashboard'] = 'FunnelDashboard';
$beanFiles['FunnelDashboard'] = 'modules/FunnelDashboard/FunnelDashboard.php';
$moduleList[] = 'FunnelDashboard';
PHPEOF

# Create the compiled extension file - SuiteCRM loads modules.ext.php (not Include.ext.php)
cat > /bitnami/suitecrm/public/legacy/custom/application/Ext/Include/modules.ext.php << 'PHPEOF'
<?php
// PowerPack Module Registration - Compiled
$beanList['TwilioIntegration'] = 'TwilioIntegration';
$beanFiles['TwilioIntegration'] = 'modules/TwilioIntegration/TwilioIntegration.php';
$moduleList[] = 'TwilioIntegration';

$beanList['LeadJourney'] = 'LeadJourney';
$beanFiles['LeadJourney'] = 'modules/LeadJourney/LeadJourney.php';
$moduleList[] = 'LeadJourney';

$beanList['FunnelDashboard'] = 'FunnelDashboard';
$beanFiles['FunnelDashboard'] = 'modules/FunnelDashboard/FunnelDashboard.php';
$moduleList[] = 'FunnelDashboard';
PHPEOF

chown -R daemon:daemon /bitnami/suitecrm/public/legacy/custom/

echo "✓ Module registration completed"

# Clear cache to ensure new modules are loaded
echo ""
echo "Clearing cache..."
rm -rf /bitnami/suitecrm/public/legacy/cache/* 2>/dev/null || true

# Create database tables
echo ""
echo "Checking database tables..."
DB_PORT="${SUITECRM_DATABASE_PORT_NUMBER:-3306}"

# SSL Configuration via environment variables:
# - MYSQL_SSL_CA: Path to CA certificate file (e.g., /path/to/ca-certificate.crt)
# - MYSQL_CLIENT_ENABLE_SSL: Set to "yes" to enable SSL (used with MYSQL_SSL_CA)
#
# For DigitalOcean managed MySQL:
#   -e MYSQL_SSL_CA=/opt/bitnami/mysql/certs/ca-certificate.crt
#   -e MYSQL_CLIENT_ENABLE_SSL=yes
#
# For local MySQL without SSL: Don't set MYSQL_SSL_CA or set MYSQL_CLIENT_ENABLE_SSL=no

SSL_CA_PATH="${MYSQL_SSL_CA:-${MYSQL_CLIENT_SSL_CA_FILE:-}}"
SSL_ENABLED="${MYSQL_CLIENT_ENABLE_SSL:-no}"

if [ "$SSL_ENABLED" = "yes" ] && [ -n "$SSL_CA_PATH" ] && [ -f "$SSL_CA_PATH" ]; then
    SSL_OPTS="--ssl-ca=$SSL_CA_PATH"
    echo "Using SSL connection to database with CA: $SSL_CA_PATH"
else
    # Default: disable SSL for MariaDB client
    SSL_OPTS="--skip-ssl"
    echo "Using standard database connection (SSL disabled)..."
fi

# Check if tables already exist
TABLES_EXIST=$(mysql -h"$SUITECRM_DATABASE_HOST" -P"$DB_PORT" -u"$SUITECRM_DATABASE_USER" -p"$SUITECRM_DATABASE_PASSWORD" $SSL_OPTS "$SUITECRM_DATABASE_NAME" -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$SUITECRM_DATABASE_NAME' AND table_name IN ('twilio_integration', 'twilio_audit_log', 'lead_journey', 'funnel_dashboard');" 2>/dev/null || echo "0")

if [ "$TABLES_EXIST" = "4" ]; then
    echo "✓ All module tables already exist, skipping migration"
else
    echo "Creating database tables (found $TABLES_EXIST/4 tables)..."
    mysql -h"$SUITECRM_DATABASE_HOST" -P"$DB_PORT" -u"$SUITECRM_DATABASE_USER" -p"$SUITECRM_DATABASE_PASSWORD" $SSL_OPTS "$SUITECRM_DATABASE_NAME" <<'EOF'

-- Twilio Integration table
CREATE TABLE IF NOT EXISTS twilio_integration (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255),
    date_entered DATETIME,
    date_modified DATETIME,
    modified_user_id VARCHAR(36),
    created_by VARCHAR(36),
    description TEXT,
    deleted TINYINT(1) DEFAULT 0,
    assigned_user_id VARCHAR(36),
    account_sid VARCHAR(255),
    auth_token VARCHAR(255),
    phone_number VARCHAR(50),
    enable_click_to_call TINYINT(1) DEFAULT 1,
    enable_auto_logging TINYINT(1) DEFAULT 1,
    enable_recordings TINYINT(1) DEFAULT 1,
    webhook_url VARCHAR(255),
    INDEX idx_assigned (assigned_user_id),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Twilio Audit Log table
CREATE TABLE IF NOT EXISTS twilio_audit_log (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    data TEXT,
    user_id VARCHAR(36),
    date_created DATETIME NOT NULL,
    INDEX idx_action (action),
    INDEX idx_user_id (user_id),
    INDEX idx_date_created (date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lead Journey table
CREATE TABLE IF NOT EXISTS lead_journey (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255),
    date_entered DATETIME,
    date_modified DATETIME,
    modified_user_id VARCHAR(36),
    created_by VARCHAR(36),
    description TEXT,
    deleted TINYINT(1) DEFAULT 0,
    parent_type VARCHAR(255),
    parent_id VARCHAR(36),
    touchpoint_type VARCHAR(50),
    touchpoint_date DATETIME,
    touchpoint_data TEXT,
    source VARCHAR(255),
    campaign_id VARCHAR(36),
    INDEX idx_parent (parent_type, parent_id),
    INDEX idx_touchpoint_date (touchpoint_date),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Funnel Dashboard table
CREATE TABLE IF NOT EXISTS funnel_dashboard (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255),
    date_entered DATETIME,
    date_modified DATETIME,
    deleted TINYINT(1) DEFAULT 0,
    category VARCHAR(255),
    funnel_config TEXT,
    INDEX idx_category (category),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

EOF

    if [ $? -eq 0 ]; then
        echo "✓ Database tables created successfully"
    else
        echo "✗ Warning: Database table creation had issues"
    fi
fi

# Clear all caches
echo ""
echo "Clearing caches..."
rm -rf /bitnami/suitecrm/cache/* 2>/dev/null || true
rm -rf /bitnami/suitecrm/public/legacy/cache/* 2>/dev/null || true
rm -rf /bitnami/suitecrm/public/legacy/themes/*/cache/* 2>/dev/null || true

# Set proper permissions
echo "Setting proper permissions..."
chown -R daemon:daemon /bitnami/suitecrm 2>/dev/null || true
chmod -R 755 /bitnami/suitecrm 2>/dev/null || true
chmod -R 777 /bitnami/suitecrm/cache 2>/dev/null || true
chmod 777 /opt/bitnami/php/var/run/session 2>/dev/null || true

echo ""
echo "============================================"
echo "✓ Module installation completed!"
echo "============================================"
echo ""
echo "Installed modules:"
echo "  • TwilioIntegration - Click-to-call and SMS"
echo "  • LeadJourney - Customer journey tracking"  
echo "  • FunnelDashboard - Sales funnel visualization"
echo ""
echo "Access them from the SuiteCRM navigation menu"
echo "or Admin > Display Modules and Subpanels"
echo ""
