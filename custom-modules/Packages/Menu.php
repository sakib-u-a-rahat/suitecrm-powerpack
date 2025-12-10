<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $mod_strings, $app_strings, $sugar_config;

$module_menu = array(
    array(
        'index.php?module=Packages&action=EditView',
        $mod_strings['LNK_NEW_RECORD'] ?? 'Create Package',
        'Packages',
        'Packages'
    ),
    array(
        'index.php?module=Packages&action=index',
        $mod_strings['LNK_LIST'] ?? 'View Packages',
        'Packages',
        'Packages'
    ),
);
