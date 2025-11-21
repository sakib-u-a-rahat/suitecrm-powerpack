<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $mod_strings, $app_strings, $sugar_config;

$module_menu = array(
    array(
        'index.php?module=LeadJourney&action=index',
        $mod_strings['LNK_LIST'] ?? 'View Lead Journeys',
        'LeadJourney',
        'LeadJourney'
    ),
    array(
        'index.php?module=LeadJourney&action=EditView',
        $mod_strings['LNK_NEW_RECORD'] ?? 'Log Journey Event',
        'Create',
        'LeadJourney'
    ),
);
