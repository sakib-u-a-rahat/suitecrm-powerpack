<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$viewdefs['SalesTargets']['DetailView'] = array(
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
        'lbl_panel_target_info' => array(
            array(
                'name',
                'target_type',
            ),
            array(
                'target_user_name',
                'funnel_type',
            ),
            array(
                'period_type',
                'period_year',
            ),
            array(
                'period_month',
                'period_quarter',
            ),
            array(
                'description',
            ),
        ),
        'lbl_panel_metrics' => array(
            array(
                'revenue_target',
                'revenue_actual',
            ),
            array(
                'demos_target',
                'demos_actual',
            ),
            array(
                'leads_target',
                'leads_actual',
            ),
            array(
                'calls_target',
                'calls_actual',
            ),
        ),
        'lbl_panel_commission' => array(
            array(
                'commission_rate',
                'commission_earned',
            ),
            array(
                'commission_paid',
                '',
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
