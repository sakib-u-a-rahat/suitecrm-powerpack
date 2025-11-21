<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $mod_strings, $app_strings, $sugar_config;

$module_menu = array(
    array(
        'index.php?module=TwilioIntegration&action=index',
        $mod_strings['LNK_LIST'] ?? 'View Twilio Integrations',
        'TwilioIntegration',
        'TwilioIntegration'
    ),
    array(
        'index.php?module=TwilioIntegration&action=EditView',
        $mod_strings['LNK_NEW_RECORD'] ?? 'Create Twilio Integration',
        'Create',
        'TwilioIntegration'
    ),
    array(
        'index.php?module=TwilioIntegration&action=config',
        'Configuration',
        'TwilioConfig',
        'TwilioIntegration'
    ),
);
