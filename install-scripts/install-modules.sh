#!/bin/bash
set -e

echo "Installing SuiteCRM custom modules..."

# Navigate to SuiteCRM legacy directory
cd /bitnami/suitecrm/public/legacy

# Function to register a module in SuiteCRM
register_module() {
    MODULE_NAME=$1
    MODULE_PATH="/bitnami/suitecrm/modules/${MODULE_NAME}"
    
    echo "Registering ${MODULE_NAME}..."
    
    if [ -d "$MODULE_PATH" ]; then
        # Register module using PHP
        php -r "
        define('sugarEntry', true);
        chdir('/bitnami/suitecrm/public/legacy');
        require_once('include/entryPoint.php');
        
        \$module = '${MODULE_NAME}';
        
        // Add module to system tabs
        if (!isset(\$GLOBALS['moduleList'])) {
            \$GLOBALS['moduleList'] = array();
        }
        if (!in_array(\$module, \$GLOBALS['moduleList'])) {
            \$GLOBALS['moduleList'][] = \$module;
        }
        
        // Register in module registry
        \$beanList = array();
        \$beanFiles = array();
        if (file_exists('modules/{\$module}/{\$module}.php')) {
            \$beanList[\$module] = \$module;
            \$beanFiles[\$module] = 'modules/{\$module}/{\$module}.php';
        }
        
        // Save configuration
        require_once('modules/Administration/Administration.php');
        \$admin = new Administration();
        \$admin->saveSetting('system', 'moduleList', base64_encode(serialize(\$GLOBALS['moduleList'])));
        
        echo \"Module \$module registered\n\";
        "
        
        # Copy extension files if they exist
        if [ -d "$MODULE_PATH/Extensions" ]; then
            echo "Installing ${MODULE_NAME} extensions..."
            cp -r "$MODULE_PATH/Extensions/"* "/bitnami/suitecrm/custom/Extension/" 2>/dev/null || true
        fi
        
        echo "${MODULE_NAME} registered successfully!"
    else
        echo "Module path not found: $MODULE_PATH"
    fi
}

# Register all modules
register_module "TwilioIntegration"
register_module "LeadJourney"
register_module "FunnelDashboard"

# Run Quick Repair and Rebuild
echo "Running Quick Repair and Rebuild..."
php -r "
define('sugarEntry', true);
chdir('/bitnami/suitecrm/public/legacy');
require_once('include/entryPoint.php');
require_once('modules/Administration/QuickRepairAndRebuild.php');

\$repair = new RepairAndClear();
\$repair->repairAndClearAll(['clearAll'], ['All'], false, false);

echo \"Quick Repair completed\n\";
"

# Create database tables
echo "Creating database tables..."
DB_PORT="${SUITECRM_DATABASE_PORT_NUMBER:-3306}"
mysql -h"$SUITECRM_DATABASE_HOST" -P"$DB_PORT" -u"$SUITECRM_DATABASE_USER" -p"$SUITECRM_DATABASE_PASSWORD" "$SUITECRM_DATABASE_NAME" <<EOF

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
    webhook_url VARCHAR(255)
);

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
    INDEX idx_touchpoint_date (touchpoint_date)
);

-- Funnel Dashboard table
CREATE TABLE IF NOT EXISTS funnel_dashboard (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255),
    date_entered DATETIME,
    date_modified DATETIME,
    deleted TINYINT(1) DEFAULT 0,
    category VARCHAR(255),
    funnel_config TEXT
);

EOF

echo "Database tables created successfully!"

# Clear cache after module installation
echo "Clearing cache..."
rm -rf /bitnami/suitecrm/cache/* 2>/dev/null || true
rm -rf /bitnami/suitecrm/public/legacy/cache/* 2>/dev/null || true
find /bitnami/suitecrm -type d -name "cache" -exec rm -rf {}/* \; 2>/dev/null || true

# Set permissions (Bitnami uses daemon user with UID 1001)
chown -R 1001:1001 /bitnami/suitecrm || true
chmod -R 755 /bitnami/suitecrm || true

echo "Module installation completed!"
