<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$manifest = array(
    'acceptable_sugar_versions' => array(
        'regex_matches' => array('7\\..*'),
    ),
    'acceptable_sugar_flavors' => array('CE', 'PRO', 'ENT'),
    'readme' => '',
    'key' => 'funnel_dashboard',
    'author' => 'SuiteCRM Extended',
    'description' => 'Funnel Dashboards - Segmented by category with stage-tracking and analytics',
    'icon' => '',
    'is_uninstallable' => true,
    'name' => 'Funnel Dashboard',
    'published_date' => '2025-11-16',
    'type' => 'module',
    'version' => '1.0.0',
    'remove_tables' => 'prompt',
);

$installdefs = array(
    'id' => 'funnel_dashboard',
    'copy' => array(
        array(
            'from' => '<basepath>/modules/FunnelDashboard',
            'to' => 'modules/FunnelDashboard',
        ),
    ),
    'beans' => array(
        array(
            'module' => 'FunnelDashboard',
            'class' => 'FunnelDashboard',
            'path' => 'modules/FunnelDashboard/FunnelDashboard.php',
            'tab' => true,
        ),
    ),
);
