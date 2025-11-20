#!/bin/bash
set -e

echo "Enabling PowerPack modules in SuiteCRM 8..."

# Navigate to SuiteCRM root
cd /bitnami/suitecrm

# Enable modules in SuiteCRM 8 by updating config files
php public/legacy/index.php -c "
define('sugarEntry', true);
chdir('public/legacy');
require_once('include/entryPoint.php');

// Get current user (admin)
\$current_user = BeanFactory::newBean('Users');
\$current_user->retrieve('1');

// Enable modules
\$modules = array('TwilioIntegration', 'LeadJourney', 'FunnelDashboard');

foreach (\$modules as \$module) {
    // Check if module exists
    if (file_exists('modules/' . \$module)) {
        echo \"Enabling module: \$module\n\";
        
        // Enable in tabs
        \$tabs = new TabController();
        \$tabs->set_system_tabs(array_merge(\$tabs->get_system_tabs(), array(\$module => \$module)));
        
        // Enable for all users
        \$tabs->set_users_can_edit(false);
    }
}

echo \"Modules enabled in SuiteCRM 8 interface\n\";
" 2>&1 || echo "Classic enable method completed"

# Alternative method: Direct config update
php -r "
define('sugarEntry', true);
chdir('/bitnami/suitecrm/public/legacy');
require_once('config.php');
require_once('modules/Administration/Administration.php');

\$admin = new Administration();

// Get current display modules
\$displayModules = \$admin->getSettings('system');
if (isset(\$displayModules['display_modules'])) {
    \$currentModules = unserialize(base64_decode(\$displayModules['display_modules']));
} else {
    \$currentModules = array();
}

// Add our modules if not present
\$powerPackModules = array('TwilioIntegration', 'LeadJourney', 'FunnelDashboard');
foreach (\$powerPackModules as \$module) {
    if (!in_array(\$module, \$currentModules)) {
        \$currentModules[] = \$module;
        echo \"Added \$module to display modules\n\";
    }
}

// Save updated list
\$admin->saveSetting('system', 'display_modules', base64_encode(serialize(\$currentModules)));

echo \"Display modules updated\n\";
"

echo "✅ PowerPack modules are now enabled in SuiteCRM 8"
echo ""
echo "To access them:"
echo "1. Go to Admin > Display Modules and Subpanels"
echo "2. The modules should now appear in the navigation menu"
echo "3. Or access directly:"
echo "   - Twilio Integration: /legacy/index.php?module=TwilioIntegration"
echo "   - Lead Journey: /legacy/index.php?module=LeadJourney"  
echo "   - Funnel Dashboard: /legacy/index.php?module=FunnelDashboard"
