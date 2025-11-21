<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $mod_strings, $app_strings, $sugar_config;

$module_menu = array(
    array(
        'index.php?module=FunnelDashboard&action=dashboard',
        $mod_strings['LNK_DASHBOARD'] ?? 'Sales Funnel Dashboard',
        'FunnelDashboard',
        'FunnelDashboard'
    ),
    array(
        'index.php?module=FunnelDashboard&action=index',
        $mod_strings['LNK_LIST'] ?? 'View Funnels',
        'FunnelDashboard',
        'FunnelDashboard'
    ),
);
