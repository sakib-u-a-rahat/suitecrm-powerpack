<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$dictionary['FunnelDashboard'] = array(
    'table' => 'funnel_dashboard',
    'audited' => true,
    'fields' => array(
        'category' => array(
            'name' => 'category',
            'vname' => 'LBL_CATEGORY',
            'type' => 'varchar',
            'len' => '255',
        ),
        'value' => array(
            'name' => 'value',
            'vname' => 'LBL_VALUE',
            'type' => 'currency',
            'dbType' => 'decimal',
            'len' => '26,6',
        ),
        'funnel_config' => array(
            'name' => 'funnel_config',
            'vname' => 'LBL_FUNNEL_CONFIG',
            'type' => 'text',
        ),
    ),
    'relationships' => array(),
    'indices' => array(),
);

VardefManager::createVardef('FunnelDashboard', 'FunnelDashboard', array('basic', 'assignable'));
