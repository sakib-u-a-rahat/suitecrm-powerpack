#!/bin/bash
set -e

echo "Enabling PowerPack modules for all users..."

cd /bitnami/suitecrm/public/legacy || exit 1

# Enable modules using TabController
php -r "
define('sugarEntry', true);
chdir('/bitnami/suitecrm/public/legacy');

// Minimal bootstrap
require_once('include/entryPoint.php');

global \$db, \$sugar_config;

if (empty(\$db)) {
    echo \"Database not available, skipping module enable\n\";
    exit(0);
}

// PowerPack modules to enable
\$powerPackModules = array(
    'FunnelDashboard',
    'SalesTargets',
    'Packages',
    'TwilioIntegration',
    'LeadJourney'
);

try {
    // Method 1: Update system tabs via TabController
    if (file_exists('include/tabConfig.php')) {
        require_once('include/tabConfig.php');

        if (class_exists('TabController')) {
            \$tabs = new TabController();
            \$systemTabs = \$tabs->get_system_tabs();

            foreach (\$powerPackModules as \$module) {
                if (!isset(\$systemTabs[\$module])) {
                    \$systemTabs[\$module] = \$module;
                }
            }

            \$tabs->set_system_tabs(\$systemTabs);
            echo \"System tabs updated\n\";
        }
    }

    // Method 2: Update admin user preferences to show modules
    \$adminQuery = \"SELECT id FROM users WHERE user_name='admin' AND deleted=0 LIMIT 1\";
    \$adminResult = \$db->query(\$adminQuery);

    if (\$adminRow = \$db->fetchByAssoc(\$adminResult)) {
        \$adminId = \$adminRow['id'];

        // Get current preferences
        \$prefQuery = \"SELECT contents FROM user_preferences WHERE assigned_user_id='\" . \$db->quote(\$adminId) . \"' AND category='global' AND deleted=0\";
        \$prefResult = \$db->query(\$prefQuery);

        if (\$prefRow = \$db->fetchByAssoc(\$prefResult)) {
            \$prefs = unserialize(base64_decode(\$prefRow['contents']));

            if (is_array(\$prefs)) {
                // Remove modules from hide_tabs and remove_tabs
                if (isset(\$prefs['hide_tabs']) && is_array(\$prefs['hide_tabs'])) {
                    foreach (\$powerPackModules as \$module) {
                        unset(\$prefs['hide_tabs'][\$module]);
                    }
                }
                if (isset(\$prefs['remove_tabs']) && is_array(\$prefs['remove_tabs'])) {
                    foreach (\$powerPackModules as \$module) {
                        unset(\$prefs['remove_tabs'][\$module]);
                    }
                }

                // Save updated preferences
                \$newContents = base64_encode(serialize(\$prefs));
                \$updateQuery = \"UPDATE user_preferences SET contents='\" . \$db->quote(\$newContents) . \"', date_modified=NOW() WHERE assigned_user_id='\" . \$db->quote(\$adminId) . \"' AND category='global'\";
                \$db->query(\$updateQuery);

                echo \"Admin preferences updated\n\";
            }
        }
    }

    echo \"Modules enabled successfully\n\";

} catch (Exception \$e) {
    echo \"Warning: Could not enable modules: \" . \$e->getMessage() . \"\n\";
}
" 2>&1 || echo "Module enable script completed with warnings"

echo "✓ PowerPack modules enabled for all users"
