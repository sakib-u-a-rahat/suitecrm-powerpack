<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$manifest = array(
    'acceptable_sugar_versions' => array(
        'regex_matches' => array('7\\..*', '8\\..*'),
    ),
    'acceptable_sugar_flavors' => array('CE', 'PRO', 'ENT'),
    'readme' => '',
    'key' => 'packages',
    'author' => 'SuiteCRM Extended',
    'description' => 'Service Packages - Define service offerings with pricing and commission structures',
    'icon' => '',
    'is_uninstallable' => true,
    'name' => 'Packages',
    'published_date' => '2025-01-01',
    'type' => 'module',
    'version' => '1.0.0',
    'remove_tables' => 'prompt',
);

$installdefs = array(
    'id' => 'packages',
    'copy' => array(
        array(
            'from' => '<basepath>/Packages.php',
            'to' => 'modules/Packages/Packages.php',
        ),
        array(
            'from' => '<basepath>/vardefs.php',
            'to' => 'modules/Packages/vardefs.php',
        ),
        array(
            'from' => '<basepath>/Menu.php',
            'to' => 'modules/Packages/Menu.php',
        ),
        array(
            'from' => '<basepath>/metadata',
            'to' => 'modules/Packages/metadata',
        ),
        array(
            'from' => '<basepath>/language',
            'to' => 'modules/Packages/language',
        ),
    ),
    'beans' => array(
        array(
            'module' => 'Packages',
            'class' => 'Packages',
            'path' => 'modules/Packages/Packages.php',
            'tab' => true,
        ),
    ),
    'language' => array(
        array(
            'from' => '<basepath>/language/en_us.lang.php',
            'to_module' => 'Packages',
            'language' => 'en_us',
        ),
    ),
);
