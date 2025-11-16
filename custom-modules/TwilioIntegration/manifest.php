<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$manifest = array(
    'acceptable_sugar_versions' => array(
        'regex_matches' => array('7\\..*'),
    ),
    'acceptable_sugar_flavors' => array('CE', 'PRO', 'ENT'),
    'readme' => '',
    'key' => 'twilio_integration',
    'author' => 'SuiteCRM Extended',
    'description' => 'Twilio Integration - Click-to-call, Auto logging, and Call recordings',
    'icon' => '',
    'is_uninstallable' => true,
    'name' => 'Twilio Integration',
    'published_date' => '2025-11-16',
    'type' => 'module',
    'version' => '1.0.0',
    'remove_tables' => 'prompt',
);

$installdefs = array(
    'id' => 'twilio_integration',
    'copy' => array(
        array(
            'from' => '<basepath>/modules/TwilioIntegration',
            'to' => 'modules/TwilioIntegration',
        ),
        array(
            'from' => '<basepath>/custom/Extension/modules/Contacts/Ext/Vardefs',
            'to' => 'custom/Extension/modules/Contacts/Ext/Vardefs',
        ),
        array(
            'from' => '<basepath>/custom/Extension/modules/Leads/Ext/Vardefs',
            'to' => 'custom/Extension/modules/Leads/Ext/Vardefs',
        ),
    ),
    'beans' => array(
        array(
            'module' => 'TwilioIntegration',
            'class' => 'TwilioIntegration',
            'path' => 'modules/TwilioIntegration/TwilioIntegration.php',
            'tab' => true,
        ),
    ),
    'layoutdefs' => array(
        array(
            'from' => '<basepath>/custom/Extension/modules/Contacts/Ext/Layoutdefs/twilio_integration.php',
            'to_module' => 'Contacts',
        ),
        array(
            'from' => '<basepath>/custom/Extension/modules/Leads/Ext/Layoutdefs/twilio_integration.php',
            'to_module' => 'Leads',
        ),
    ),
);
