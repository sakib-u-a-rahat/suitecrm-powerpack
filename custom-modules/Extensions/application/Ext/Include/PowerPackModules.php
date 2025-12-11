<?php
// PowerPack Module Registration
// Note: No die() check - this file is compiled into ext.php

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
