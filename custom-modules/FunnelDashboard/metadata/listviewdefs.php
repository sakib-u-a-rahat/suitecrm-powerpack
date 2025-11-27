<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$listViewDefs['FunnelDashboard'] = array(
    'NAME' => array(
        'width' => '30%',
        'label' => 'LBL_NAME',
        'default' => true,
        'link' => true,
    ),
    'CATEGORY' => array(
        'width' => '20%',
        'label' => 'LBL_CATEGORY',
        'default' => true,
    ),
    'VALUE' => array(
        'width' => '15%',
        'label' => 'LBL_VALUE',
        'default' => true,
        'currency_format' => true,
    ),
    'ASSIGNED_USER_NAME' => array(
        'width' => '15%',
        'label' => 'LBL_ASSIGNED_USER',
        'default' => true,
        'link' => true,
    ),
    'DATE_ENTERED' => array(
        'width' => '20%',
        'label' => 'LBL_DATE_ENTERED',
        'default' => true,
    ),
);
