<?php
/**
 * InboundEmail List View
 * Redirects to config page
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/MVC/View/SugarView.php');

class InboundEmailViewList extends SugarView
{
    public function display()
    {
        header('Location: index.php?module=InboundEmail&action=config');
        exit;
    }
}
