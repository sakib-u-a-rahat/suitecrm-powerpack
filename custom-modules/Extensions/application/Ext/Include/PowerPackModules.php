<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

// Add PowerPack modules to displayed module list
global $moduleList;

$powerPackModules = array(
    'FunnelDashboard',
    'SalesTargets',
    'Packages',
    'TwilioIntegration',
    'LeadJourney'
);

foreach ($powerPackModules as $module) {
    if (!in_array($module, $moduleList)) {
        $moduleList[] = $module;
    }
}
