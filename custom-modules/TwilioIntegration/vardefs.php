<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$dictionary['TwilioIntegration'] = array(
    'table' => 'twilio_integration',
    'audited' => true,
    'inline_edit' => true,
    'duplicate_merge' => true,
    'fields' => array(
        'account_sid' => array(
            'name' => 'account_sid',
            'vname' => 'LBL_ACCOUNT_SID',
            'type' => 'varchar',
            'len' => '255',
            'required' => true,
        ),
        'auth_token' => array(
            'name' => 'auth_token',
            'vname' => 'LBL_AUTH_TOKEN',
            'type' => 'varchar',
            'len' => '255',
            'required' => true,
        ),
        'phone_number' => array(
            'name' => 'phone_number',
            'vname' => 'LBL_PHONE_NUMBER',
            'type' => 'phone',
            'required' => true,
        ),
        'enable_click_to_call' => array(
            'name' => 'enable_click_to_call',
            'vname' => 'LBL_ENABLE_CLICK_TO_CALL',
            'type' => 'bool',
            'default' => 1,
        ),
        'enable_auto_logging' => array(
            'name' => 'enable_auto_logging',
            'vname' => 'LBL_ENABLE_AUTO_LOGGING',
            'type' => 'bool',
            'default' => 1,
        ),
        'enable_recordings' => array(
            'name' => 'enable_recordings',
            'vname' => 'LBL_ENABLE_RECORDINGS',
            'type' => 'bool',
            'default' => 1,
        ),
        'webhook_url' => array(
            'name' => 'webhook_url',
            'vname' => 'LBL_WEBHOOK_URL',
            'type' => 'url',
        ),
    ),
    'relationships' => array(),
    'indices' => array(),
);

VardefManager::createVardef('TwilioIntegration', 'TwilioIntegration', array('basic', 'assignable'));
