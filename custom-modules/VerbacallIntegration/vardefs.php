<?php
/**
 * VerbacallIntegration Module - Vardefs
 *
 * This module provides Verbacall integration and doesn't store records,
 * but SugarBean requires vardefs for proper initialization.
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$dictionary['VerbacallIntegration'] = [
    'table' => 'verbacall_integration',
    'audited' => false,
    'duplicate_merge' => false,
    'fields' => [
        'id' => [
            'name' => 'id',
            'vname' => 'LBL_ID',
            'type' => 'id',
            'required' => true,
        ],
        'name' => [
            'name' => 'name',
            'vname' => 'LBL_NAME',
            'type' => 'name',
            'len' => 100,
            'required' => true,
        ],
        'date_entered' => [
            'name' => 'date_entered',
            'vname' => 'LBL_DATE_ENTERED',
            'type' => 'datetime',
        ],
        'date_modified' => [
            'name' => 'date_modified',
            'vname' => 'LBL_DATE_MODIFIED',
            'type' => 'datetime',
        ],
        'deleted' => [
            'name' => 'deleted',
            'vname' => 'LBL_DELETED',
            'type' => 'bool',
            'default' => 0,
        ],
    ],
    'indices' => [
        [
            'name' => 'verbacall_integration_pk',
            'type' => 'primary',
            'fields' => ['id'],
        ],
    ],
];
