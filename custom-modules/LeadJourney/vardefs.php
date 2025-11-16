<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$dictionary['LeadJourney'] = array(
    'table' => 'lead_journey',
    'audited' => true,
    'fields' => array(
        'parent_type' => array(
            'name' => 'parent_type',
            'vname' => 'LBL_PARENT_TYPE',
            'type' => 'varchar',
            'len' => '255',
            'required' => true,
        ),
        'parent_id' => array(
            'name' => 'parent_id',
            'vname' => 'LBL_PARENT_ID',
            'type' => 'id',
            'required' => true,
        ),
        'touchpoint_type' => array(
            'name' => 'touchpoint_type',
            'vname' => 'LBL_TOUCHPOINT_TYPE',
            'type' => 'enum',
            'options' => 'touchpoint_type_list',
            'required' => true,
        ),
        'touchpoint_date' => array(
            'name' => 'touchpoint_date',
            'vname' => 'LBL_TOUCHPOINT_DATE',
            'type' => 'datetime',
            'required' => true,
        ),
        'touchpoint_data' => array(
            'name' => 'touchpoint_data',
            'vname' => 'LBL_TOUCHPOINT_DATA',
            'type' => 'text',
        ),
        'source' => array(
            'name' => 'source',
            'vname' => 'LBL_SOURCE',
            'type' => 'varchar',
            'len' => '255',
        ),
        'campaign_id' => array(
            'name' => 'campaign_id',
            'vname' => 'LBL_CAMPAIGN_ID',
            'type' => 'id',
        ),
    ),
    'relationships' => array(),
    'indices' => array(
        array(
            'name' => 'idx_parent',
            'type' => 'index',
            'fields' => array('parent_type', 'parent_id'),
        ),
        array(
            'name' => 'idx_touchpoint_date',
            'type' => 'index',
            'fields' => array('touchpoint_date'),
        ),
    ),
);

VardefManager::createVardef('LeadJourney', 'LeadJourney', array('basic', 'assignable'));
