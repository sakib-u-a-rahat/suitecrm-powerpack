<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$manifest = array(
    'acceptable_sugar_versions' => array(
        'regex_matches' => array('7\\..*', '8\\..*'),
    ),
    'acceptable_sugar_flavors' => array('CE', 'PRO', 'ENT'),
    'readme' => '',
    'key' => 'sales_targets',
    'author' => 'SuiteCRM Extended',
    'description' => 'Sales Targets - Track revenue, demo, and lead targets per BDM/Team with commission tracking',
    'icon' => '',
    'is_uninstallable' => true,
    'name' => 'Sales Targets',
    'published_date' => '2025-01-01',
    'type' => 'module',
    'version' => '1.0.0',
    'remove_tables' => 'prompt',
);

$installdefs = array(
    'id' => 'sales_targets',
    'copy' => array(
        array(
            'from' => '<basepath>/SalesTargets.php',
            'to' => 'modules/SalesTargets/SalesTargets.php',
        ),
        array(
            'from' => '<basepath>/vardefs.php',
            'to' => 'modules/SalesTargets/vardefs.php',
        ),
        array(
            'from' => '<basepath>/Menu.php',
            'to' => 'modules/SalesTargets/Menu.php',
        ),
        array(
            'from' => '<basepath>/metadata',
            'to' => 'modules/SalesTargets/metadata',
        ),
        array(
            'from' => '<basepath>/language',
            'to' => 'modules/SalesTargets/language',
        ),
    ),
    'beans' => array(
        array(
            'module' => 'SalesTargets',
            'class' => 'SalesTargets',
            'path' => 'modules/SalesTargets/SalesTargets.php',
            'tab' => true,
        ),
    ),
    'language' => array(
        array(
            'from' => '<basepath>/language/en_us.lang.php',
            'to_module' => 'SalesTargets',
            'language' => 'en_us',
        ),
    ),
);
