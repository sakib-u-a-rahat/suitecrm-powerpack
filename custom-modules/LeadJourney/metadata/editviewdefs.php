<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$viewdefs['LeadJourney']['EditView'] = array(
    'templateMeta' => array(
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
    ),
    'panels' => array(
        'default' => array(
            array(
                array('name' => 'name', 'label' => 'LBL_NAME'),
                array('name' => 'touchpoint_type', 'label' => 'LBL_TOUCHPOINT_TYPE'),
            ),
            array(
                array('name' => 'parent_name', 'label' => 'LBL_PARENT'),
                array('name' => 'touchpoint_date', 'label' => 'LBL_TOUCHPOINT_DATE'),
            ),
            array(
                array('name' => 'source', 'label' => 'LBL_SOURCE'),
                array('name' => 'campaign_name', 'label' => 'LBL_CAMPAIGN'),
            ),
            array(
                array('name' => 'description', 'label' => 'LBL_DESCRIPTION', 'colspan' => '2'),
            ),
            array(
                array('name' => 'assigned_user_name', 'label' => 'LBL_ASSIGNED_USER'),
                '',
            ),
        ),
    ),
);
