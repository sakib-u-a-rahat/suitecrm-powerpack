<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$listViewDefs['Packages'] = array(
    'NAME' => array(
        'width' => '20%',
        'label' => 'LBL_LIST_NAME',
        'default' => true,
        'link' => true,
    ),
    'PACKAGE_CODE' => array(
        'width' => '10%',
        'label' => 'LBL_PACKAGE_CODE',
        'default' => true,
    ),
    'PACKAGE_TYPE' => array(
        'width' => '12%',
        'label' => 'LBL_LIST_PACKAGE_TYPE',
        'default' => true,
    ),
    'PRICE' => array(
        'width' => '12%',
        'label' => 'LBL_LIST_PRICE',
        'default' => true,
        'currency_format' => true,
    ),
    'BILLING_FREQUENCY' => array(
        'width' => '10%',
        'label' => 'LBL_LIST_BILLING',
        'default' => true,
    ),
    'COMMISSION_RATE' => array(
        'width' => '10%',
        'label' => 'LBL_LIST_COMMISSION',
        'default' => true,
    ),
    'IS_ACTIVE' => array(
        'width' => '8%',
        'label' => 'LBL_LIST_ACTIVE',
        'default' => true,
    ),
    'ASSIGNED_USER_NAME' => array(
        'width' => '10%',
        'label' => 'LBL_ASSIGNED_USER',
        'default' => false,
        'link' => true,
        'id' => 'ASSIGNED_USER_ID',
        'module' => 'Users',
    ),
);
