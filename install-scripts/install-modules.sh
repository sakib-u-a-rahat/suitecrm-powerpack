#!/bin/bash
set -e

echo "Installing SuiteCRM custom modules..."

# Navigate to SuiteCRM directory
cd /opt/bitnami/suitecrm

# Function to install a module
install_module() {
    MODULE_NAME=$1
    MODULE_PATH="/opt/bitnami/suitecrm/custom/modules/${MODULE_NAME}"
    
    echo "Installing ${MODULE_NAME}..."
    
    if [ -d "$MODULE_PATH" ]; then
        # Copy module files
        cp -r "$MODULE_PATH" "/opt/bitnami/suitecrm/modules/"
        
        # Run repair and rebuild
        php -r "
        define('sugarEntry', true);
        require_once('include/entryPoint.php');
        require_once('modules/Administration/QuickRepairAndRebuild.php');
        \$repair = new RepairAndClear();
        \$repair->repairAndClearAll(['clearAll'], ['All'], false, false);
        "
        
        echo "${MODULE_NAME} installed successfully!"
    else
        echo "Module path not found: $MODULE_PATH"
    fi
}

# Install modules
install_module "TwilioIntegration"
install_module "LeadJourney"
install_module "FunnelDashboard"

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

# Set permissions (Bitnami uses daemon user with UID 1001)
chown -R 1001:1001 /opt/bitnami/suitecrm || true
chmod -R 755 /opt/bitnami/suitecrm || true

echo "Module installation completed!"
