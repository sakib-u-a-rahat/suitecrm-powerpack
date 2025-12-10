<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $mod_strings, $app_strings, $sugar_config;

$module_menu = array(
    array(
        'index.php?module=SalesTargets&action=EditView',
        $mod_strings['LNK_NEW_RECORD'] ?? 'Create Target',
        'SalesTargets',
        'SalesTargets'
    ),
    array(
        'index.php?module=SalesTargets&action=index',
        $mod_strings['LNK_LIST'] ?? 'View Targets',
        'SalesTargets',
        'SalesTargets'
    ),
    array(
        'index.php?module=SalesTargets&action=leaderboard',
        $mod_strings['LNK_LEADERBOARD'] ?? 'Leaderboard',
        'SalesTargets',
        'SalesTargets'
    ),
);
