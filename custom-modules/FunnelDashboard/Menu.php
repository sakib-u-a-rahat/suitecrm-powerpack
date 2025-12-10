<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $mod_strings, $app_strings, $sugar_config, $current_user;

$module_menu = array();

// Main dashboard - always show
$module_menu[] = array(
    'index.php?module=FunnelDashboard&action=dashboard',
    $mod_strings['LNK_DASHBOARD'] ?? 'Sales Funnel Dashboard',
    'FunnelDashboard',
    'FunnelDashboard'
);

// Admin users see all dashboards
$isAdmin = !empty($current_user) && method_exists($current_user, 'isAdmin') && $current_user->isAdmin();

// CRO Dashboard
if ($isAdmin || _checkFunnelDashboardAccess('crodashboard')) {
    $module_menu[] = array(
        'index.php?module=FunnelDashboard&action=crodashboard',
        $mod_strings['LNK_CRO_DASHBOARD'] ?? 'CRO Dashboard',
        'FunnelDashboard',
        'FunnelDashboard'
    );
}

// Sales Ops Dashboard
if ($isAdmin || _checkFunnelDashboardAccess('salesopsdashboard')) {
    $module_menu[] = array(
        'index.php?module=FunnelDashboard&action=salesopsdashboard',
        $mod_strings['LNK_SALESOPS_DASHBOARD'] ?? 'Sales Ops Dashboard',
        'FunnelDashboard',
        'FunnelDashboard'
    );
}

// BDM Dashboard
if ($isAdmin || _checkFunnelDashboardAccess('bdmdashboard')) {
    $module_menu[] = array(
        'index.php?module=FunnelDashboard&action=bdmdashboard',
        $mod_strings['LNK_BDM_DASHBOARD'] ?? 'BDM Dashboard',
        'FunnelDashboard',
        'FunnelDashboard'
    );
}

// List view - always show
$module_menu[] = array(
    'index.php?module=FunnelDashboard&action=index',
    $mod_strings['LNK_LIST'] ?? 'View Funnels',
    'FunnelDashboard',
    'FunnelDashboard'
);

/**
 * Check if current user has access to a dashboard action via ACL
 */
function _checkFunnelDashboardAccess($action) {
    global $current_user, $db;

    if (empty($current_user) || empty($current_user->id) || empty($db)) {
        return false;
    }

    try {
        $userId = $db->quoted($current_user->id);
        $actionName = $db->quoted($action);

        $query = "
            SELECT ara.access_override
            FROM acl_roles_actions ara
            INNER JOIN acl_roles_users aru ON ara.role_id = aru.role_id AND aru.deleted = 0
            INNER JOIN acl_actions aa ON ara.action_id = aa.id AND aa.deleted = 0
            WHERE aru.user_id = $userId
            AND aa.category = 'FunnelDashboard'
            AND aa.name = $actionName
            AND ara.deleted = 0
            ORDER BY ara.access_override DESC
            LIMIT 1
        ";

        $result = $db->query($query);
        if ($row = $db->fetchByAssoc($result)) {
            return (int)$row['access_override'] >= 0;
        }
    } catch (Exception $e) {
        // Silently fail - no access
    }

    return false;
}
