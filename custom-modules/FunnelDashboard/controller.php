<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

#[\AllowDynamicProperties]
class FunnelDashboardController extends SugarController
{
    /**
     * Index action - redirect to main dashboard
     * This is called by SuiteCRM 8's Angular frontend when navigating to the module
     */
    public function action_index()
    {
        $this->view = 'dashboard';
    }

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
