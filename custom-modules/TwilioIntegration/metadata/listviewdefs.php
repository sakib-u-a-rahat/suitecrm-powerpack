<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$listViewDefs['TwilioIntegration'] = array(
    'NAME' => array(
        'width' => '25%',
        'label' => 'LBL_NAME',
        'default' => true,
        'link' => true,
    ),
    'PHONE_NUMBER' => array(
        'width' => '20%',
        'label' => 'LBL_PHONE_NUMBER',
        'default' => true,
    ),
    'ENABLE_CLICK_TO_CALL' => array(
        'width' => '15%',
        'label' => 'LBL_ENABLE_CLICK_TO_CALL',
        'default' => true,
    ),
    'ENABLE_SMS' => array(
        'width' => '10%',
        'label' => 'LBL_ENABLE_SMS',
        'default' => true,
    ),
    'ASSIGNED_USER_NAME' => array(
        'width' => '15%',
        'label' => 'LBL_ASSIGNED_USER',
        'default' => true,
        'link' => true,
    ),
    'DATE_ENTERED' => array(
        'width' => '15%',
        'label' => 'LBL_DATE_ENTERED',
        'default' => true,
    ),
);
