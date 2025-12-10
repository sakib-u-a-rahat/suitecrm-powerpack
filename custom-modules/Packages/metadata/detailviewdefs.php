<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$viewdefs['Packages']['DetailView'] = array(
    'templateMeta' => array(
        'form' => array(
            'buttons' => array('EDIT', 'DUPLICATE', 'DELETE'),
        ),
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
                'description',
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
                'date_modified',
            ),
            array(
                'date_entered',
                '',
            ),
        ),
    ),
);
