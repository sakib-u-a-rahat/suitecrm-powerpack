<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$dictionary['LeadJourney'] = array(
    'table' => 'lead_journey',
    'audited' => true,
    'fields' => array(
        'parent_type' => array(
            'name' => 'parent_type',
            'vname' => 'LBL_PARENT_TYPE',
            'type' => 'parent_type',
            'dbType' => 'varchar',
            'len' => '255',
            'required' => false,
            'group' => 'parent_name',
            'options' => 'lead_journey_parent_type_list',
        ),
        'parent_id' => array(
            'name' => 'parent_id',
            'vname' => 'LBL_PARENT_ID',
            'type' => 'id',
            'required' => false,
            'reportable' => false,
        ),
        'parent_name' => array(
            'name' => 'parent_name',
            'vname' => 'LBL_PARENT',
            'type' => 'parent',
            'type_name' => 'parent_type',
            'id_name' => 'parent_id',
            'source' => 'non-db',
            'options' => 'lead_journey_parent_type_list',
        ),
        'touchpoint_type' => array(
            'name' => 'touchpoint_type',
            'vname' => 'LBL_TOUCHPOINT_TYPE',
            'type' => 'enum',
            'options' => 'touchpoint_type_list',
            'required' => true,
            'len' => 100,
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
            'reportable' => false,
        ),
        'campaign_name' => array(
            'name' => 'campaign_name',
            'rname' => 'name',
            'id_name' => 'campaign_id',
            'vname' => 'LBL_CAMPAIGN',
            'type' => 'relate',
            'table' => 'campaigns',
            'isnull' => true,
            'module' => 'Campaigns',
            'dbType' => 'varchar',
            'link' => 'campaign_link',
            'len' => '255',
            'source' => 'non-db',
        ),
        'campaign_link' => array(
            'name' => 'campaign_link',
            'type' => 'link',
            'relationship' => 'lead_journey_campaigns',
            'source' => 'non-db',
            'vname' => 'LBL_CAMPAIGN',
        ),
    ),
    'relationships' => array(
        'lead_journey_campaigns' => array(
            'lhs_module' => 'Campaigns',
            'lhs_table' => 'campaigns',
            'lhs_key' => 'id',
            'rhs_module' => 'LeadJourney',
            'rhs_table' => 'lead_journey',
            'rhs_key' => 'campaign_id',
            'relationship_type' => 'one-to-many',
        ),
    ),
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
