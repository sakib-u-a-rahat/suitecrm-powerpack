<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$manifest = array(
    'acceptable_sugar_versions' => array(
        'regex_matches' => array('7\\..*'),
    ),
    'acceptable_sugar_flavors' => array('CE', 'PRO', 'ENT'),
    'readme' => '',
    'key' => 'lead_journey_timeline',
    'author' => 'SuiteCRM Extended',
    'description' => 'Lead Journey Timeline - Unified view of all touchpoints including calls, emails, site visits, and LinkedIn clicks',
    'icon' => '',
    'is_uninstallable' => true,
    'name' => 'Lead Journey Timeline',
    'published_date' => '2025-11-16',
    'type' => 'module',
    'version' => '1.0.0',
    'remove_tables' => 'prompt',
);

$installdefs = array(
    'id' => 'lead_journey_timeline',
    'copy' => array(
        array(
            'from' => '<basepath>/modules/LeadJourney',
            'to' => 'modules/LeadJourney',
        ),
    ),
    'beans' => array(
        array(
            'module' => 'LeadJourney',
            'class' => 'LeadJourney',
            'path' => 'modules/LeadJourney/LeadJourney.php',
            'tab' => true,
        ),
    ),
);
