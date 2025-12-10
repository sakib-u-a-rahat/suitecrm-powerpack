<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/ACLActions/SugarACLStrategy.php');

class SugarACLFunnelDashboard extends SugarACLStrategy
{
    /**
     * Check access to FunnelDashboard actions
     */
    public function checkAccess($module, $view, $context)
    {
        global $current_user;

        if ($view == 'field') {
            return true;
        }

        // Admin always has access
        if ($current_user->isAdmin()) {
            return true;
        }

        // Check role-based access for dashboard views
        $userTitle = strtolower($current_user->title ?? '');

        switch ($view) {
            case 'crodashboard':
                // CRO Dashboard - only for CRO, CEO, executives
                return (
                    strpos($userTitle, 'cro') !== false ||
                    strpos($userTitle, 'ceo') !== false ||
                    strpos($userTitle, 'chief') !== false ||
                    strpos($userTitle, 'executive') !== false ||
                    strpos($userTitle, 'director') !== false
                );

            case 'salesopsdashboard':
                // Sales Ops Dashboard - for sales ops, managers, admins
                return (
                    strpos($userTitle, 'sales op') !== false ||
                    strpos($userTitle, 'manager') !== false ||
                    strpos($userTitle, 'admin') !== false ||
                    strpos($userTitle, 'supervisor') !== false
                );

            case 'bdmdashboard':
                // BDM Dashboard - for BDMs, sales reps
                return (
                    strpos($userTitle, 'bdm') !== false ||
                    strpos($userTitle, 'business development') !== false ||
                    strpos($userTitle, 'sales rep') !== false ||
                    strpos($userTitle, 'account exec') !== false ||
                    strpos($userTitle, 'representative') !== false
                );

            case 'dashboard':
            case 'list':
            case 'view':
            case 'detail':
                // General access - allow all authenticated users
                return true;

            default:
                return true;
        }
    }
}
