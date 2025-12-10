<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class SalesTargets extends Basic {
    public $new_schema = true;
    public $module_dir = 'SalesTargets';
    public $object_name = 'SalesTargets';
    public $table_name = 'sales_targets';
    public $importable = true;

    public $id;
    public $name;
    public $date_entered;
    public $date_modified;
    public $modified_user_id;
    public $created_by;
    public $description;
    public $deleted;
    public $assigned_user_id;

    // Target specific fields
    public $target_type; // 'bdm', 'team'
    public $target_user_id; // BDM user ID
    public $team_id; // Team ID if team target
    public $funnel_type; // Realtors, Senior_Living, Home_Care
    public $period_type; // monthly, quarterly, annual
    public $period_year;
    public $period_month;
    public $period_quarter;

    // Target values
    public $revenue_target;
    public $revenue_actual;
    public $demos_target;
    public $demos_actual;
    public $leads_target;
    public $leads_actual;
    public $calls_target;
    public $calls_actual;

    // Commission
    public $commission_rate;
    public $commission_earned;
    public $commission_paid;

    public function __construct() {
        parent::__construct();
    }

    public function bean_implements($interface) {
        switch($interface) {
            case 'ACL': return true;
        }
        return false;
    }

    /**
     * Get targets for a specific user (BDM)
     */
    public static function getTargetsForUser($userId, $funnelType = null, $periodType = 'monthly', $year = null, $month = null) {
        global $db;

        $year = $year ?: date('Y');
        $month = $month ?: date('n');

        $query = "SELECT * FROM sales_targets
                  WHERE target_user_id = " . $db->quoted($userId) . "
                  AND target_type = 'bdm'
                  AND period_type = " . $db->quoted($periodType) . "
                  AND period_year = " . (int)$year . "
                  AND deleted = 0";

        if ($periodType === 'monthly') {
            $query .= " AND period_month = " . (int)$month;
        }

        if ($funnelType && $funnelType !== 'all') {
            $query .= " AND funnel_type = " . $db->quoted($funnelType);
        }

        $result = $db->query($query);
        $targets = array();

        while ($row = $db->fetchByAssoc($result)) {
            $targets[] = $row;
        }

        return $targets;
    }

    /**
     * Get targets for a team
     */
    public static function getTargetsForTeam($teamId, $funnelType = null, $periodType = 'monthly', $year = null, $month = null) {
        global $db;

        $year = $year ?: date('Y');
        $month = $month ?: date('n');

        $query = "SELECT * FROM sales_targets
                  WHERE team_id = " . $db->quoted($teamId) . "
                  AND target_type = 'team'
                  AND period_type = " . $db->quoted($periodType) . "
                  AND period_year = " . (int)$year . "
                  AND deleted = 0";

        if ($periodType === 'monthly') {
            $query .= " AND period_month = " . (int)$month;
        }

        if ($funnelType && $funnelType !== 'all') {
            $query .= " AND funnel_type = " . $db->quoted($funnelType);
        }

        $result = $db->query($query);
        $targets = array();

        while ($row = $db->fetchByAssoc($result)) {
            $targets[] = $row;
        }

        return $targets;
    }

    /**
     * Calculate achievement percentage for a target
     */
    public static function calculateAchievement($targetId) {
        global $db;

        $query = "SELECT revenue_target, revenue_actual, demos_target, demos_actual,
                         leads_target, leads_actual, calls_target, calls_actual
                  FROM sales_targets WHERE id = " . $db->quoted($targetId);

        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);

        if (!$row) return null;

        $achievements = array();

        // Revenue achievement
        if ($row['revenue_target'] > 0) {
            $achievements['revenue'] = round(($row['revenue_actual'] / $row['revenue_target']) * 100, 2);
        }

        // Demos achievement
        if ($row['demos_target'] > 0) {
            $achievements['demos'] = round(($row['demos_actual'] / $row['demos_target']) * 100, 2);
        }

        // Leads achievement
        if ($row['leads_target'] > 0) {
            $achievements['leads'] = round(($row['leads_actual'] / $row['leads_target']) * 100, 2);
        }

        // Calls achievement
        if ($row['calls_target'] > 0) {
            $achievements['calls'] = round(($row['calls_actual'] / $row['calls_target']) * 100, 2);
        }

        // Overall achievement (average of all metrics)
        $achievements['overall'] = count($achievements) > 0
            ? round(array_sum($achievements) / count($achievements), 2)
            : 0;

        return $achievements;
    }

    /**
     * Update actual values from opportunities and activities
     */
    public static function updateActualsForUser($userId, $year = null, $month = null) {
        global $db;

        $year = $year ?: date('Y');
        $month = $month ?: date('n');

        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // Calculate revenue from closed won opportunities
        $revenueQuery = "SELECT COALESCE(SUM(o.amount), 0) as total_revenue
                         FROM opportunities o
                         WHERE o.assigned_user_id = " . $db->quoted($userId) . "
                         AND o.sales_stage = 'Closed Won'
                         AND o.date_closed BETWEEN " . $db->quoted($startDate) . " AND " . $db->quoted($endDate) . "
                         AND o.deleted = 0";
        $revenueResult = $db->query($revenueQuery);
        $revenueRow = $db->fetchByAssoc($revenueResult);
        $revenue = (float)($revenueRow['total_revenue'] ?? 0);

        // Calculate demos from meetings
        $demosQuery = "SELECT COUNT(*) as demo_count
                       FROM meetings m
                       WHERE m.assigned_user_id = " . $db->quoted($userId) . "
                       AND m.status = 'Held'
                       AND m.date_start BETWEEN " . $db->quoted($startDate) . " AND " . $db->quoted($endDate) . "
                       AND m.deleted = 0
                       AND (m.name LIKE '%demo%' OR m.name LIKE '%Demo%' OR m.name LIKE '%visit%' OR m.name LIKE '%Visit%')";
        $demosResult = $db->query($demosQuery);
        $demosRow = $db->fetchByAssoc($demosResult);
        $demos = (int)($demosRow['demo_count'] ?? 0);

        // Calculate leads converted
        $leadsQuery = "SELECT COUNT(*) as lead_count
                       FROM leads l
                       WHERE l.assigned_user_id = " . $db->quoted($userId) . "
                       AND l.status = 'Converted'
                       AND l.date_modified BETWEEN " . $db->quoted($startDate) . " AND " . $db->quoted($endDate) . "
                       AND l.deleted = 0";
        $leadsResult = $db->query($leadsQuery);
        $leadsRow = $db->fetchByAssoc($leadsResult);
        $leads = (int)($leadsRow['lead_count'] ?? 0);

        // Calculate calls made
        $callsQuery = "SELECT COUNT(*) as call_count
                       FROM calls c
                       WHERE c.assigned_user_id = " . $db->quoted($userId) . "
                       AND c.status = 'Held'
                       AND c.date_start BETWEEN " . $db->quoted($startDate) . " AND " . $db->quoted($endDate) . "
                       AND c.deleted = 0";
        $callsResult = $db->query($callsQuery);
        $callsRow = $db->fetchByAssoc($callsResult);
        $calls = (int)($callsRow['call_count'] ?? 0);

        // Update all targets for this user/month
        $updateQuery = "UPDATE sales_targets
                        SET revenue_actual = $revenue,
                            demos_actual = $demos,
                            leads_actual = $leads,
                            calls_actual = $calls,
                            date_modified = NOW()
                        WHERE target_user_id = " . $db->quoted($userId) . "
                        AND target_type = 'bdm'
                        AND period_type = 'monthly'
                        AND period_year = $year
                        AND period_month = $month
                        AND deleted = 0";

        $db->query($updateQuery);

        return array(
            'revenue' => $revenue,
            'demos' => $demos,
            'leads' => $leads,
            'calls' => $calls
        );
    }

    /**
     * Get leaderboard for a period
     */
    public static function getLeaderboard($funnelType = null, $periodType = 'monthly', $year = null, $month = null, $limit = 10) {
        global $db;

        $year = $year ?: date('Y');
        $month = $month ?: date('n');

        $query = "SELECT st.*, u.first_name, u.last_name,
                         CASE WHEN st.revenue_target > 0
                              THEN ROUND((st.revenue_actual / st.revenue_target) * 100, 2)
                              ELSE 0 END as achievement_pct
                  FROM sales_targets st
                  JOIN users u ON st.target_user_id = u.id
                  WHERE st.target_type = 'bdm'
                  AND st.period_type = " . $db->quoted($periodType) . "
                  AND st.period_year = " . (int)$year . "
                  AND st.deleted = 0
                  AND u.deleted = 0";

        if ($periodType === 'monthly') {
            $query .= " AND st.period_month = " . (int)$month;
        }

        if ($funnelType && $funnelType !== 'all') {
            $query .= " AND st.funnel_type = " . $db->quoted($funnelType);
        }

        $query .= " ORDER BY achievement_pct DESC, st.revenue_actual DESC
                    LIMIT " . (int)$limit;

        $result = $db->query($query);
        $leaderboard = array();
        $rank = 1;

        while ($row = $db->fetchByAssoc($result)) {
            $row['rank'] = $rank++;
            $row['bdm_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $leaderboard[] = $row;
        }

        return $leaderboard;
    }

    /**
     * Calculate commission for a user based on their target achievement
     */
    public static function calculateCommission($userId, $year = null, $month = null) {
        global $db;

        $year = $year ?: date('Y');
        $month = $month ?: date('n');

        // Get target with commission rate
        $query = "SELECT * FROM sales_targets
                  WHERE target_user_id = " . $db->quoted($userId) . "
                  AND target_type = 'bdm'
                  AND period_type = 'monthly'
                  AND period_year = " . (int)$year . "
                  AND period_month = " . (int)$month . "
                  AND deleted = 0
                  LIMIT 1";

        $result = $db->query($query);
        $target = $db->fetchByAssoc($result);

        if (!$target) return 0;

        $commissionRate = (float)($target['commission_rate'] ?? 5); // Default 5%
        $revenue = (float)($target['revenue_actual'] ?? 0);

        // Base commission
        $commission = $revenue * ($commissionRate / 100);

        // Bonus tiers based on achievement
        $achievement = self::calculateAchievement($target['id']);
        if ($achievement && isset($achievement['revenue'])) {
            if ($achievement['revenue'] >= 120) {
                $commission *= 1.2; // 20% bonus for 120%+ achievement
            } elseif ($achievement['revenue'] >= 100) {
                $commission *= 1.1; // 10% bonus for 100%+ achievement
            }
        }

        // Update commission earned
        $updateQuery = "UPDATE sales_targets
                        SET commission_earned = " . $commission . ",
                            date_modified = NOW()
                        WHERE id = " . $db->quoted($target['id']);
        $db->query($updateQuery);

        return round($commission, 2);
    }

    /**
     * Get underperforming BDMs (below threshold)
     */
    public static function getUnderperformingBDMs($threshold = 70, $year = null, $month = null) {
        global $db;

        $year = $year ?: date('Y');
        $month = $month ?: date('n');

        $query = "SELECT st.*, u.first_name, u.last_name,
                         CASE WHEN st.revenue_target > 0
                              THEN ROUND((st.revenue_actual / st.revenue_target) * 100, 2)
                              ELSE 0 END as achievement_pct
                  FROM sales_targets st
                  JOIN users u ON st.target_user_id = u.id
                  WHERE st.target_type = 'bdm'
                  AND st.period_type = 'monthly'
                  AND st.period_year = " . (int)$year . "
                  AND st.period_month = " . (int)$month . "
                  AND st.deleted = 0
                  AND u.deleted = 0
                  HAVING achievement_pct < " . (int)$threshold . "
                  ORDER BY achievement_pct ASC";

        $result = $db->query($query);
        $underperformers = array();

        while ($row = $db->fetchByAssoc($result)) {
            $row['bdm_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $underperformers[] = $row;
        }

        return $underperformers;
    }
}
