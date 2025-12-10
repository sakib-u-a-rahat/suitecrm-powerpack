<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

#[\AllowDynamicProperties]
class FunnelDashboardController extends SugarController
{
    /**
     * Default dashboard view
     */
    public function action_dashboard()
    {
        $this->view = 'dashboard';
    }

    /**
     * CRO Dashboard - Strategic/Oversight Role
     */
    public function action_crodashboard()
    {
        $this->view = 'crodashboard';
    }

    /**
     * Sales Ops Dashboard - Operations/Workflow Control
     */
    public function action_salesopsdashboard()
    {
        $this->view = 'salesopsdashboard';
    }

    /**
     * BDM Dashboard - Business Development Manager
     */
    public function action_bdmdashboard()
    {
        $this->view = 'bdmdashboard';
    }
}
