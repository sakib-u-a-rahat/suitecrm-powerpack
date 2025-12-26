<?php
/**
 * InboundEmail Module Menu
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

global $mod_strings, $app_strings;

$module_menu = [];

$module_menu[] = [
    'index.php?module=InboundEmail&action=config',
    'Configure Email Accounts',
    'InboundEmail',
    'InboundEmail'
];

$module_menu[] = [
    'index.php?module=InboundEmail&action=list',
    'Email Accounts',
    'InboundEmail',
    'InboundEmail'
];

$module_menu[] = [
    'index.php?module=InboundEmail&action=test',
    'Test Connection',
    'InboundEmail',
    'InboundEmail'
];
