<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('include/MVC/View/views/view.list.php');

/**
 * FunnelDashboard Index View
 * 
 * Default view when navigating to the FunnelDashboard module.
 * Shows the standard list view - users can navigate to dashboards via menu.
 */
class FunnelDashboardViewIndex extends ViewList
{
    public function __construct()
    {
        parent::__construct();
        $this->options['show_title'] = true;
    }
}
