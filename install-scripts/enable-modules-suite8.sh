#!/bin/bash
set -e

echo "Enabling PowerPack modules for all users..."

cd /bitnami/suitecrm/public/legacy || exit 1

# Make modules visible to all users
php -r "
define('sugarEntry', true);
\$_SERVER['REQUEST_METHOD'] = 'GET';
require_once('include/entryPoint.php');

// Enable modules for all users
require_once('include/tabConfig.php');
\$tabs = new TabController();

// Get system tabs
\$systemTabs = \$tabs->get_system_tabs();

// Add PowerPack modules
\$modules = array('TwilioIntegration', 'LeadJourney', 'FunnelDashboard');
foreach (\$modules as \$module) {
    \$systemTabs[\$module] = \$module;
}

\$tabs->set_system_tabs(\$systemTabs);

// Make modules visible to all users (not hidden)
\$tabs->set_users_can_edit(false);

// Set default tabs for new users
\$displayTabs = \$tabs->get_system_tabs();
foreach (\$modules as \$module) {
    \$displayTabs[\$module] = \$module;
}

echo \"Modules enabled successfully\n\";
"

echo "✓ PowerPack modules enabled for all users"
