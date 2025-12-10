<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$listViewDefs['SalesTargets'] = array(
    'NAME' => array(
        'width' => '15%',
        'label' => 'LBL_LIST_NAME',
        'default' => true,
        'link' => true,
    ),
    'TARGET_TYPE' => array(
        'width' => '8%',
        'label' => 'LBL_LIST_TARGET_TYPE',
        'default' => true,
    ),
    'TARGET_USER_NAME' => array(
        'width' => '12%',
        'label' => 'LBL_LIST_TARGET_USER',
        'default' => true,
        'link' => true,
        'id' => 'TARGET_USER_ID',
        'module' => 'Users',
    ),
    'FUNNEL_TYPE' => array(
        'width' => '10%',
        'label' => 'LBL_LIST_FUNNEL',
        'default' => true,
    ),
    'PERIOD_YEAR' => array(
        'width' => '6%',
        'label' => 'LBL_PERIOD_YEAR',
        'default' => true,
    ),
    'PERIOD_MONTH' => array(
        'width' => '6%',
        'label' => 'LBL_PERIOD_MONTH',
        'default' => true,
    ),
    'REVENUE_TARGET' => array(
        'width' => '10%',
        'label' => 'LBL_LIST_REVENUE_TARGET',
        'default' => true,
        'currency_format' => true,
    ),
    'REVENUE_ACTUAL' => array(
        'width' => '10%',
        'label' => 'LBL_LIST_REVENUE_ACTUAL',
        'default' => true,
        'currency_format' => true,
    ),
    'COMMISSION_EARNED' => array(
        'width' => '10%',
        'label' => 'LBL_COMMISSION_EARNED',
        'default' => true,
        'currency_format' => true,
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
