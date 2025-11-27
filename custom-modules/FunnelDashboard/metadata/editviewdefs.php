<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$viewdefs['FunnelDashboard']['EditView'] = array(
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
                array('name' => 'assigned_user_name', 'label' => 'LBL_ASSIGNED_USER'),
            ),
            array(
                array('name' => 'category', 'label' => 'LBL_CATEGORY'),
                array('name' => 'value', 'label' => 'LBL_VALUE'),
            ),
            array(
                array('name' => 'description', 'label' => 'LBL_DESCRIPTION'),
            ),
        ),
    ),
);
