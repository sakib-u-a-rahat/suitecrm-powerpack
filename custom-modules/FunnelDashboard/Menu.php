<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $mod_strings, $app_strings, $sugar_config, $current_user;

$module_menu = array(
    array(
        'index.php?module=FunnelDashboard&action=dashboard',
        $mod_strings['LNK_DASHBOARD'] ?? 'Sales Funnel Dashboard',
        'FunnelDashboard',
        'FunnelDashboard'
    ),
    array(
        'index.php?module=FunnelDashboard&action=crodashboard',
        $mod_strings['LNK_CRO_DASHBOARD'] ?? 'CRO Dashboard',
        'FunnelDashboard',
        'FunnelDashboard'
    ),
    array(
        'index.php?module=FunnelDashboard&action=salesopsdashboard',
        $mod_strings['LNK_SALESOPS_DASHBOARD'] ?? 'Sales Ops Dashboard',
        'FunnelDashboard',
        'FunnelDashboard'
    ),
    array(
        'index.php?module=FunnelDashboard&action=bdmdashboard',
        $mod_strings['LNK_BDM_DASHBOARD'] ?? 'BDM Dashboard',
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
