<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$viewdefs['Packages']['EditView'] = array(
    'templateMeta' => array(
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
    ),
    'panels' => array(
        'lbl_panel_package_info' => array(
            array(
                'name',
                'package_code',
            ),
            array(
                'package_type',
                'is_active',
            ),
            array(
                array('name' => 'description', 'displayParams' => array('rows' => 3, 'cols' => 60)),
            ),
        ),
        'lbl_panel_pricing' => array(
            array(
                'price',
                'billing_frequency',
            ),
            array(
                'commission_rate',
                'commission_flat',
            ),
        ),
        'LBL_PANEL_ASSIGNMENT' => array(
            array(
                'assigned_user_name',
                '',
            ),
        ),
    ),
);
