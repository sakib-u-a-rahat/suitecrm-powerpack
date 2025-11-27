<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$searchdefs['FunnelDashboard'] = array(
    'layout' => array(
        'basic_search' => array(
            'name' => array('name' => 'name', 'label' => 'LBL_NAME', 'default' => true),
            'current_user_only' => array('name' => 'current_user_only', 'label' => 'LBL_CURRENT_USER_FILTER', 'type' => 'bool', 'default' => true),
        ),
        'advanced_search' => array(
            'name' => array('name' => 'name', 'label' => 'LBL_NAME', 'default' => true),
            'category' => array('name' => 'category', 'label' => 'LBL_CATEGORY', 'default' => true),
            'assigned_user_id' => array('name' => 'assigned_user_id', 'label' => 'LBL_ASSIGNED_USER', 'type' => 'enum', 'function' => array('name' => 'get_user_array', 'params' => array(false)), 'default' => true),
        ),
    ),
    'templateMeta' => array(
        'maxColumns' => '3',
        'widths' => array('label' => '10', 'field' => '30'),
    ),
);
