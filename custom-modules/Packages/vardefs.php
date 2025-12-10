<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$dictionary['Packages'] = array(
    'table' => 'packages',
    'audited' => true,
    'fields' => array(
        'package_code' => array(
            'name' => 'package_code',
            'vname' => 'LBL_PACKAGE_CODE',
            'type' => 'varchar',
            'len' => 50,
            'required' => false,
        ),
        'package_type' => array(
            'name' => 'package_type',
            'vname' => 'LBL_PACKAGE_TYPE',
            'type' => 'enum',
            'options' => 'funnel_type_list',
            'len' => 100,
            'comment' => 'Which funnel this package belongs to',
        ),
        'price' => array(
            'name' => 'price',
            'vname' => 'LBL_PRICE',
            'type' => 'currency',
            'dbType' => 'decimal',
            'len' => '26,6',
            'default' => 0,
        ),
        'billing_frequency' => array(
            'name' => 'billing_frequency',
            'vname' => 'LBL_BILLING_FREQUENCY',
            'type' => 'enum',
            'options' => 'billing_frequency_list',
            'len' => 50,
            'default' => 'one-time',
        ),
        'commission_rate' => array(
            'name' => 'commission_rate',
            'vname' => 'LBL_COMMISSION_RATE',
            'type' => 'decimal',
            'len' => '5,2',
            'default' => 5.00,
            'comment' => 'Commission percentage',
        ),
        'commission_flat' => array(
            'name' => 'commission_flat',
            'vname' => 'LBL_COMMISSION_FLAT',
            'type' => 'currency',
            'dbType' => 'decimal',
            'len' => '26,6',
            'default' => 0,
            'comment' => 'Flat commission amount (overrides rate)',
        ),
        'features' => array(
            'name' => 'features',
            'vname' => 'LBL_FEATURES',
            'type' => 'text',
            'comment' => 'JSON array of features',
        ),
        'is_active' => array(
            'name' => 'is_active',
            'vname' => 'LBL_IS_ACTIVE',
            'type' => 'bool',
            'default' => 1,
        ),
    ),
    'relationships' => array(),
    'indices' => array(
        array(
            'name' => 'idx_package_type',
            'type' => 'index',
            'fields' => array('package_type'),
        ),
        array(
            'name' => 'idx_package_code',
            'type' => 'index',
            'fields' => array('package_code'),
        ),
        array(
            'name' => 'idx_is_active',
            'type' => 'index',
            'fields' => array('is_active', 'deleted'),
        ),
    ),
);

VardefManager::createVardef('Packages', 'Packages', array('basic', 'assignable'));
