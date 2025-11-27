<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$viewdefs['TwilioIntegration']['EditView'] = array(
    'templateMeta' => array(
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
    ),
    'panels' => array(
        'LBL_PANEL_BASIC' => array(
            array(
                array('name' => 'name', 'label' => 'LBL_NAME'),
                array('name' => 'assigned_user_name', 'label' => 'LBL_ASSIGNED_USER'),
            ),
        ),
        'LBL_PANEL_TWILIO' => array(
            array(
                array('name' => 'account_sid', 'label' => 'LBL_ACCOUNT_SID'),
                array('name' => 'auth_token', 'label' => 'LBL_AUTH_TOKEN'),
            ),
            array(
                array('name' => 'phone_number', 'label' => 'LBL_PHONE_NUMBER'),
                array('name' => 'webhook_url', 'label' => 'LBL_WEBHOOK_URL'),
            ),
        ),
        'LBL_PANEL_OPTIONS' => array(
            array(
                array('name' => 'enable_click_to_call', 'label' => 'LBL_ENABLE_CLICK_TO_CALL'),
                array('name' => 'enable_auto_logging', 'label' => 'LBL_ENABLE_AUTO_LOGGING'),
            ),
            array(
                array('name' => 'enable_recordings', 'label' => 'LBL_ENABLE_RECORDINGS'),
                '',
            ),
        ),
        'LBL_PANEL_DESCRIPTION' => array(
            array(
                array('name' => 'description', 'label' => 'LBL_DESCRIPTION', 'colspan' => '2'),
            ),
        ),
    ),
);
