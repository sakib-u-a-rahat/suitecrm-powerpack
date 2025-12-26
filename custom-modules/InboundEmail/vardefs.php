<?php
/**
 * InboundEmail vardefs - Field definitions
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$dictionary['InboundEmail'] = [
    'table' => 'inbound_email_config',
    'audited' => true,
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
            'type' => 'varchar',
            'len' => 255,
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
        'modified_user_id' => [
            'name' => 'modified_user_id',
            'vname' => 'LBL_MODIFIED_USER_ID',
            'type' => 'id',
        ],
        'created_by' => [
            'name' => 'created_by',
            'vname' => 'LBL_CREATED_BY',
            'type' => 'id',
        ],
        'description' => [
            'name' => 'description',
            'vname' => 'LBL_DESCRIPTION',
            'type' => 'text',
        ],
        'deleted' => [
            'name' => 'deleted',
            'vname' => 'LBL_DELETED',
            'type' => 'bool',
            'default' => 0,
        ],
        'assigned_user_id' => [
            'name' => 'assigned_user_id',
            'vname' => 'LBL_ASSIGNED_USER_ID',
            'type' => 'id',
        ],
        'server' => [
            'name' => 'server',
            'vname' => 'LBL_SERVER',
            'type' => 'varchar',
            'len' => 255,
            'required' => true,
            'comment' => 'IMAP/POP3 server hostname',
        ],
        'port' => [
            'name' => 'port',
            'vname' => 'LBL_PORT',
            'type' => 'int',
            'len' => 5,
            'default' => 993,
            'comment' => 'Server port (993 for IMAPS, 143 for IMAP)',
        ],
        'protocol' => [
            'name' => 'protocol',
            'vname' => 'LBL_PROTOCOL',
            'type' => 'enum',
            'options' => 'inbound_email_protocol_list',
            'default' => 'imap',
            'len' => 10,
        ],
        'username' => [
            'name' => 'username',
            'vname' => 'LBL_USERNAME',
            'type' => 'varchar',
            'len' => 255,
            'required' => true,
        ],
        'password_enc' => [
            'name' => 'password_enc',
            'vname' => 'LBL_PASSWORD',
            'type' => 'varchar',
            'len' => 500,
            'reportable' => false,
            'comment' => 'Encrypted password',
        ],
        'ssl' => [
            'name' => 'ssl',
            'vname' => 'LBL_SSL',
            'type' => 'bool',
            'default' => 1,
        ],
        'folder' => [
            'name' => 'folder',
            'vname' => 'LBL_FOLDER',
            'type' => 'varchar',
            'len' => 100,
            'default' => 'INBOX',
        ],
        'polling_interval' => [
            'name' => 'polling_interval',
            'vname' => 'LBL_POLLING_INTERVAL',
            'type' => 'int',
            'len' => 5,
            'default' => 300,
            'comment' => 'Polling interval in seconds',
        ],
        'last_poll_date' => [
            'name' => 'last_poll_date',
            'vname' => 'LBL_LAST_POLL_DATE',
            'type' => 'datetime',
        ],
        'last_uid' => [
            'name' => 'last_uid',
            'vname' => 'LBL_LAST_UID',
            'type' => 'int',
            'default' => 0,
            'comment' => 'Last processed email UID',
        ],
        'status' => [
            'name' => 'status',
            'vname' => 'LBL_STATUS',
            'type' => 'enum',
            'options' => 'inbound_email_status_list',
            'default' => 'active',
            'len' => 20,
        ],
        'auto_import' => [
            'name' => 'auto_import',
            'vname' => 'LBL_AUTO_IMPORT',
            'type' => 'bool',
            'default' => 1,
            'comment' => 'Automatically import and link emails',
        ],
        'delete_after_import' => [
            'name' => 'delete_after_import',
            'vname' => 'LBL_DELETE_AFTER_IMPORT',
            'type' => 'bool',
            'default' => 0,
            'comment' => 'Delete emails from server after import',
        ],
    ],
    'indices' => [
        [
            'name' => 'inbound_email_config_pk',
            'type' => 'primary',
            'fields' => ['id'],
        ],
        [
            'name' => 'idx_inbound_email_status',
            'type' => 'index',
            'fields' => ['status', 'deleted'],
        ],
    ],
];
