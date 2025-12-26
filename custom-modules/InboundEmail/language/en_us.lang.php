<?php
/**
 * InboundEmail Language Strings
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$mod_strings = [
    // Module
    'LBL_MODULE_NAME' => 'Inbound Email',
    'LBL_MODULE_TITLE' => 'Inbound Email Configuration',

    // Fields
    'LBL_ID' => 'ID',
    'LBL_NAME' => 'Account Name',
    'LBL_DATE_ENTERED' => 'Date Created',
    'LBL_DATE_MODIFIED' => 'Date Modified',
    'LBL_MODIFIED_USER_ID' => 'Modified By ID',
    'LBL_CREATED_BY' => 'Created By',
    'LBL_DESCRIPTION' => 'Description',
    'LBL_DELETED' => 'Deleted',
    'LBL_ASSIGNED_USER_ID' => 'Assigned User',

    // Email server config
    'LBL_SERVER' => 'Server',
    'LBL_PORT' => 'Port',
    'LBL_PROTOCOL' => 'Protocol',
    'LBL_USERNAME' => 'Username',
    'LBL_PASSWORD' => 'Password',
    'LBL_SSL' => 'Use SSL/TLS',
    'LBL_FOLDER' => 'Folder',
    'LBL_POLLING_INTERVAL' => 'Polling Interval (seconds)',
    'LBL_LAST_POLL_DATE' => 'Last Poll Date',
    'LBL_LAST_UID' => 'Last UID',
    'LBL_STATUS' => 'Status',
    'LBL_AUTO_IMPORT' => 'Auto Import',
    'LBL_DELETE_AFTER_IMPORT' => 'Delete After Import',

    // List view
    'LBL_LIST_NAME' => 'Account Name',
    'LBL_LIST_SERVER' => 'Server',
    'LBL_LIST_STATUS' => 'Status',
    'LBL_LIST_LAST_POLL' => 'Last Poll',

    // Actions
    'LBL_CONFIG' => 'Configure',
    'LBL_TEST_CONNECTION' => 'Test Connection',
    'LBL_FETCH_NOW' => 'Fetch Now',
    'LBL_SAVE' => 'Save',
    'LBL_CANCEL' => 'Cancel',

    // Messages
    'LBL_CONNECTION_SUCCESS' => 'Connection successful!',
    'LBL_CONNECTION_FAILED' => 'Connection failed: ',
    'LBL_EMAILS_FETCHED' => 'emails fetched successfully',
    'LBL_NO_NEW_EMAILS' => 'No new emails found',
    'LBL_ACCOUNT_SAVED' => 'Email account saved successfully',
    'LBL_ACCOUNT_DELETED' => 'Email account deleted',

    // Help text
    'LBL_HELP_SERVER' => 'Enter the IMAP server hostname (e.g., imap.gmail.com)',
    'LBL_HELP_PORT' => 'Standard ports: 993 (IMAPS), 143 (IMAP), 995 (POP3S), 110 (POP3)',
    'LBL_HELP_SSL' => 'Enable for secure connections (recommended)',
    'LBL_HELP_FOLDER' => 'Email folder to monitor (default: INBOX)',
    'LBL_HELP_POLLING' => 'How often to check for new emails (minimum: 60 seconds)',
    'LBL_HELP_AUTO_IMPORT' => 'Automatically link emails to matching Leads/Contacts',
    'LBL_HELP_DELETE_AFTER' => 'Remove emails from server after importing (use with caution)',

    // Config page
    'LBL_CONFIG_TITLE' => 'Inbound Email Configuration',
    'LBL_CONFIG_INTRO' => 'Configure IMAP email accounts to automatically import inbound emails and link them to your CRM records.',
    'LBL_ADD_ACCOUNT' => 'Add Email Account',
    'LBL_EDIT_ACCOUNT' => 'Edit Email Account',
    'LBL_SERVER_SETTINGS' => 'Server Settings',
    'LBL_IMPORT_SETTINGS' => 'Import Settings',

    // Status
    'LBL_STATUS_ACTIVE' => 'Active',
    'LBL_STATUS_INACTIVE' => 'Inactive',
    'LBL_STATUS_ERROR' => 'Error',

    // Errors
    'ERR_IMAP_NOT_AVAILABLE' => 'PHP IMAP extension is not installed. Please contact your administrator.',
    'ERR_INVALID_SERVER' => 'Invalid server hostname',
    'ERR_INVALID_CREDENTIALS' => 'Invalid username or password',
    'ERR_CONNECTION_TIMEOUT' => 'Connection timed out',
    'ERR_SSL_REQUIRED' => 'SSL/TLS connection required by server',
];
