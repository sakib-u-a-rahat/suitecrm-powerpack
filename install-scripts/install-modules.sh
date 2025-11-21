#!/bin/bash
set -e

echo "============================================"
echo "Installing SuiteCRM PowerPack Modules"
echo "============================================"

# Change to SuiteCRM directory
cd /bitnami/suitecrm/public/legacy || exit 1

# Ensure custom directories exist
echo "Creating custom directories..."
mkdir -p /bitnami/suitecrm/custom/Extension/application/Ext/Include
mkdir -p /bitnami/suitecrm/custom/Extension/modules
mkdir -p /bitnami/suitecrm/custom/modules

# Copy extension files
echo "Installing module extensions..."
if [ -d "/bitnami/suitecrm/modules/LeadJourney/Extensions" ]; then
    cp -r /bitnami/suitecrm/modules/LeadJourney/Extensions/* /bitnami/suitecrm/custom/Extension/ 2>/dev/null || true
fi

if [ -d "/bitnami/suitecrm/modules/TwilioIntegration/Extensions" ]; then
    cp -r /bitnami/suitecrm/modules/TwilioIntegration/Extensions/* /bitnami/suitecrm/custom/Extension/ 2>/dev/null || true
fi

# Register modules in SuiteCRM
echo "Registering modules in SuiteCRM..."

php -r "
define('sugarEntry', true);
\$_SERVER['REQUEST_METHOD'] = 'GET';
require_once('include/entryPoint.php');

global \$beanList, \$beanFiles, \$moduleList;

// Define modules to install
\$modules = array('TwilioIntegration', 'LeadJourney', 'FunnelDashboard');

// Load current configuration
if (file_exists('config.php')) {
    require_once('config.php');
    global \$sugar_config;
}

// Initialize arrays if needed
if (!isset(\$sugar_config['moduleList'])) {
    \$sugar_config['moduleList'] = array();
}

// Register each module
foreach (\$modules as \$module) {
    echo \"Registering module: \$module\n\";
    
    // Add to module list
    if (!in_array(\$module, \$sugar_config['moduleList'])) {
        \$sugar_config['moduleList'][] = \$module;
        echo \"  Added to moduleList\n\";
    }
    
    // Register in bean list
    if (!isset(\$beanList[\$module])) {
        \$beanList[\$module] = \$module;
        \$beanFiles[\$module] = 'modules/' . \$module . '/' . \$module . '.php';
        echo \"  Added to beanList\n\";
    }
}

// Save configuration using Administration
require_once('modules/Administration/Administration.php');
\$admin = new Administration();

// Update display modules
\$admin->saveSetting('system', 'display_modules', base64_encode(serialize(\$sugar_config['moduleList'])));
echo \"Display modules updated\n\";

// Enable modules in tab controller
require_once('include/tabConfig.php');
\$tabs = new TabController();
\$systemTabs = \$tabs->get_system_tabs();

foreach (\$modules as \$module) {
    if (!isset(\$systemTabs[\$module])) {
        \$systemTabs[\$module] = \$module;
        echo \"Module \$module added to system tabs\n\";
    }
}

\$tabs->set_system_tabs(\$systemTabs);
echo \"System tabs updated\n\";

// Write to custom module registry
\$customInclude = '../../custom/Extension/application/Ext/Include/powerpack_modules.php';
\$includeContent = \"<?php\\n// PowerPack Module Registration\\n\";
foreach (\$modules as \$module) {
    \$includeContent .= \"\\\$beanList['\$module'] = '\$module';\\n\";
    \$includeContent .= \"\\\$beanFiles['\$module'] = 'modules/\$module/\$module.php';\\n\";
    \$includeContent .= \"\\\$moduleList[] = '\$module';\\n\";
}
file_put_contents(\$customInclude, \$includeContent);
echo \"Custom module registry created\n\";

echo \"\\nAll modules registered successfully!\\n\";
" || echo "Module registration had warnings but continuing..."

# Run Quick Repair and Rebuild
echo ""
echo "Running Quick Repair and Rebuild..."
php -r "
define('sugarEntry', true);
\$_SERVER['REQUEST_METHOD'] = 'GET';
require_once('include/entryPoint.php');
require_once('modules/Administration/QuickRepairAndRebuild.php');

\$repair = new RepairAndClear();
\$repair->repairAndClearAll(['clearAll'], ['All'], false, false);

echo \"Quick Repair completed successfully\n\";
" || echo "Quick Repair had warnings but continuing..."

# Create database tables
echo ""
echo "Creating database tables..."
DB_PORT="${SUITECRM_DATABASE_PORT_NUMBER:-3306}"

# Check if SSL certificate exists
if [ -f "/opt/bitnami/mysql/certs/ca-certificate.crt" ]; then
    SSL_MODE="--ssl-mode=REQUIRED --ssl-ca=/opt/bitnami/mysql/certs/ca-certificate.crt"
else
    SSL_MODE=""
fi

mysql -h"$SUITECRM_DATABASE_HOST" -P"$DB_PORT" -u"$SUITECRM_DATABASE_USER" -p"$SUITECRM_DATABASE_PASSWORD" $SSL_MODE "$SUITECRM_DATABASE_NAME" <<'EOF'

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
