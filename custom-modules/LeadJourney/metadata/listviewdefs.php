<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$listViewDefs['LeadJourney'] = array(
    'NAME' => array(
        'width' => '20%',
        'label' => 'LBL_NAME',
        'default' => true,
        'link' => true,
    ),
    'PARENT_NAME' => array(
        'width' => '20%',
        'label' => 'LBL_PARENT',
        'default' => true,
        'link' => true,
        'id' => 'PARENT_ID',
        'related_fields' => array('parent_id', 'parent_type'),
    ),
    'TOUCHPOINT_TYPE' => array(
        'width' => '15%',
        'label' => 'LBL_TOUCHPOINT_TYPE',
        'default' => true,
    ),
    'TOUCHPOINT_DATE' => array(
        'width' => '20%',
        'label' => 'LBL_TOUCHPOINT_DATE',
        'default' => true,
    ),
    'SOURCE' => array(
        'width' => '15%',
        'label' => 'LBL_SOURCE',
        'default' => true,
    ),
    'DATE_ENTERED' => array(
        'width' => '10%',
        'label' => 'LBL_DATE_ENTERED',
        'default' => true,
    ),
);
