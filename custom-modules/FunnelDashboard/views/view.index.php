<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('include/MVC/View/SugarView.php');

/**
 * FunnelDashboard Index View
 * 
 * This view is called by SuiteCRM 8's Angular frontend when navigating to the module.
 * It redirects to the main dashboard view for a better user experience.
 */
class FunnelDashboardViewIndex extends SugarView
{
    public function display()
    {
        // Redirect to the main dashboard view
        header('Location: index.php?module=FunnelDashboard&action=dashboard');
        exit;
    }
}
