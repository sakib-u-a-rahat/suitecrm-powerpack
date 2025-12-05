<?php
/**
 * Twilio Metrics API View
 * Returns call and SMS statistics for dashboards
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/TwilioIntegration/TwilioIntegration.php');

class TwilioIntegrationViewMetrics extends SugarView
{
    public function display()
    {
        global $current_user;
        
        header('Content-Type: application/json');
        
        // Check access
        if (!$current_user || !$current_user->id) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            die();
        }
        
        $period = isset($_REQUEST['period']) ? $_REQUEST['period'] : '30days';
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'all';
        $userId = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;
        
        $metrics = [];
        
        switch ($type) {
            case 'calls':
                $metrics = $this->getCallMetrics($period, $userId);
                break;
            case 'sms':
                $metrics = $this->getSMSMetrics($period, $userId);
                break;
            case 'summary':
                $metrics = $this->getSummaryMetrics($period, $userId);
                break;
            case 'performance':
                $metrics = $this->getPerformanceMetrics($period, $userId);
                break;
            case 'response_time':
                $metrics = $this->getResponseTimeMetrics($period, $userId);
                break;
            default:
                $metrics = [
                    'calls' => $this->getCallMetrics($period, $userId),
                    'sms' => $this->getSMSMetrics($period, $userId),
                    'summary' => $this->getSummaryMetrics($period, $userId),
                    'response_time' => $this->getResponseTimeMetrics($period, $userId)
                ];
        }
        
        echo json_encode([
            'success' => true,
            'period' => $period,
            'data' => $metrics,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        
        die();
    }
    
    /**
     * Get call metrics
     */
    private function getCallMetrics($period, $userId = null)
    {
        $db = DBManagerFactory::getInstance();
        $dateFilter = $this->getDateFilter($period);
        $userFilter = $userId ? " AND assigned_user_id = '" . $db->quote($userId) . "'" : "";
        
        // Total calls
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN direction = 'Outbound' THEN 1 ELSE 0 END) as outbound,
                    SUM(CASE WHEN direction = 'Inbound' THEN 1 ELSE 0 END) as inbound,
                    SUM(CASE WHEN status = 'Held' THEN 1 ELSE 0 END) as connected,
                    SUM(CASE WHEN status = 'Not Held' THEN 1 ELSE 0 END) as missed,
                    SUM(duration_hours * 3600 + duration_minutes * 60) as total_duration,
                    AVG(duration_hours * 3600 + duration_minutes * 60) as avg_duration
                FROM calls 
                WHERE deleted = 0 
                AND date_start >= '$dateFilter'
                $userFilter";
        
        $result = $db->query($sql);
        $row = $db->fetchByAssoc($result);
        
        // Daily breakdown
        $dailySql = "SELECT 
                        DATE(date_start) as date,
                        COUNT(*) as calls,
                        SUM(CASE WHEN status = 'Held' THEN 1 ELSE 0 END) as connected
                    FROM calls 
                    WHERE deleted = 0 
                    AND date_start >= '$dateFilter'
                    $userFilter
                    GROUP BY DATE(date_start)
                    ORDER BY date DESC
                    LIMIT 30";
        
        $dailyResult = $db->query($dailySql);
        $daily = [];
        while ($dailyRow = $db->fetchByAssoc($dailyResult)) {
            $daily[] = $dailyRow;
        }
        
        return [
            'totals' => [
                'total' => intval($row['total']),
                'outbound' => intval($row['outbound']),
                'inbound' => intval($row['inbound']),
                'connected' => intval($row['connected']),
                'missed' => intval($row['missed']),
                'total_duration_minutes' => round(intval($row['total_duration']) / 60, 1),
                'avg_duration_seconds' => round(floatval($row['avg_duration']), 0),
                'connect_rate' => $row['total'] > 0 ? round(($row['connected'] / $row['total']) * 100, 1) : 0
            ],
            'daily' => $daily
        ];
    }
    
    /**
     * Get SMS metrics
     */
    private function getSMSMetrics($period, $userId = null)
    {
        $db = DBManagerFactory::getInstance();
        $dateFilter = $this->getDateFilter($period);
        $userFilter = $userId ? " AND assigned_user_id = '" . $db->quote($userId) . "'" : "";
        
        // Count SMS from notes
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN name LIKE '%📤%' OR name LIKE 'SMS to%' THEN 1 ELSE 0 END) as outbound,
                    SUM(CASE WHEN name LIKE '%📥%' OR name LIKE 'SMS from%' THEN 1 ELSE 0 END) as inbound
                FROM notes 
                WHERE deleted = 0 
                AND (name LIKE '%SMS%' OR description LIKE '%Twilio Message SID%')
                AND date_entered >= '$dateFilter'
                $userFilter";
        
        $result = $db->query($sql);
        $row = $db->fetchByAssoc($result);
        
        // Daily breakdown
        $dailySql = "SELECT 
                        DATE(date_entered) as date,
                        COUNT(*) as messages
                    FROM notes 
                    WHERE deleted = 0 
                    AND (name LIKE '%SMS%' OR description LIKE '%Twilio Message SID%')
                    AND date_entered >= '$dateFilter'
                    $userFilter
                    GROUP BY DATE(date_entered)
                    ORDER BY date DESC
                    LIMIT 30";
        
        $dailyResult = $db->query($dailySql);
        $daily = [];
        while ($dailyRow = $db->fetchByAssoc($dailyResult)) {
            $daily[] = $dailyRow;
        }
        
        return [
            'totals' => [
                'total' => intval($row['total']),
                'outbound' => intval($row['outbound']),
                'inbound' => intval($row['inbound'])
            ],
            'daily' => $daily
        ];
    }
    
    /**
     * Get summary metrics for quick dashboard display
     */
    private function getSummaryMetrics($period, $userId = null)
    {
        $calls = $this->getCallMetrics($period, $userId);
        $sms = $this->getSMSMetrics($period, $userId);
        
        return [
            'total_calls' => $calls['totals']['total'],
            'total_sms' => $sms['totals']['total'],
            'total_communications' => $calls['totals']['total'] + $sms['totals']['total'],
            'call_connect_rate' => $calls['totals']['connect_rate'],
            'missed_calls' => $calls['totals']['missed'],
            'avg_call_duration' => $calls['totals']['avg_duration_seconds'],
            'outbound_activity' => $calls['totals']['outbound'] + $sms['totals']['outbound'],
            'inbound_activity' => $calls['totals']['inbound'] + $sms['totals']['inbound']
        ];
    }
    
    /**
     * Get user performance metrics
     */
    private function getPerformanceMetrics($period, $userId = null)
    {
        $db = DBManagerFactory::getInstance();
        $dateFilter = $this->getDateFilter($period);
        
        // Get per-user metrics
        $sql = "SELECT 
                    u.id as user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    COUNT(c.id) as total_calls,
                    SUM(CASE WHEN c.direction = 'Outbound' THEN 1 ELSE 0 END) as outbound_calls,
                    SUM(CASE WHEN c.status = 'Held' THEN 1 ELSE 0 END) as connected_calls,
                    SUM(c.duration_hours * 3600 + c.duration_minutes * 60) as total_duration
                FROM users u
                LEFT JOIN calls c ON c.assigned_user_id = u.id AND c.deleted = 0 AND c.date_start >= '$dateFilter'
                WHERE u.deleted = 0 AND u.status = 'Active'
                GROUP BY u.id, u.first_name, u.last_name
                HAVING total_calls > 0
                ORDER BY total_calls DESC
                LIMIT 20";
        
        if ($userId) {
            $userIdSafe = $db->quote($userId);
            $sql = str_replace("WHERE u.deleted = 0", "WHERE u.deleted = 0 AND u.id = '$userIdSafe'", $sql);
        }
        
        $result = $db->query($sql);
        $users = [];
        while ($row = $db->fetchByAssoc($result)) {
            $row['connect_rate'] = $row['total_calls'] > 0 ? round(($row['connected_calls'] / $row['total_calls']) * 100, 1) : 0;
            $row['avg_duration'] = $row['total_calls'] > 0 ? round($row['total_duration'] / $row['total_calls'], 0) : 0;
            $users[] = $row;
        }
        
        return [
            'users' => $users,
            'period' => $period
        ];
    }
    
    /**
     * Get response time metrics
     * Calculates first response time for inbound activities
     */
    private function getResponseTimeMetrics($period, $userId = null)
    {
        $db = DBManagerFactory::getInstance();
        $dateFilter = $this->getDateFilter($period);
        $userFilter = $userId ? " AND c.assigned_user_id = '" . $db->quote($userId) . "'" : "";

        // Calculate first response time for inbound calls
        $callResponseSql = "SELECT
                                inbound.id as inbound_id,
                                inbound.date_start as inbound_time,
                                MIN(outbound.date_start) as response_time,
                                TIMESTAMPDIFF(MINUTE, inbound.date_start, MIN(outbound.date_start)) as response_minutes,
                                inbound.parent_type,
                                inbound.parent_id
                            FROM calls inbound
                            LEFT JOIN calls outbound ON
                                outbound.parent_id = inbound.parent_id
                                AND outbound.parent_type = inbound.parent_type
                                AND outbound.direction = 'Outbound'
                                AND outbound.date_start > inbound.date_start
                                AND outbound.deleted = 0
                            WHERE inbound.deleted = 0
                            AND inbound.direction = 'Inbound'
                            AND inbound.date_start >= '$dateFilter'
                            $userFilter
                            GROUP BY inbound.id, inbound.date_start, inbound.parent_type, inbound.parent_id
                            HAVING response_time IS NOT NULL";

        $result = $db->query($callResponseSql);
        $callResponseTimes = [];
        $totalCallResponseTime = 0;
        $callResponseCount = 0;

        while ($row = $db->fetchByAssoc($result)) {
            $callResponseTimes[] = [
                'type' => 'call',
                'inbound_time' => $row['inbound_time'],
                'response_time' => $row['response_time'],
                'response_minutes' => intval($row['response_minutes'])
            ];
            $totalCallResponseTime += intval($row['response_minutes']);
            $callResponseCount++;
        }

        // Calculate first response time for inbound SMS
        $smsResponseSql = "SELECT
                               inbound.id as inbound_id,
                               inbound.date_entered as inbound_time,
                               MIN(outbound.date_entered) as response_time,
                               TIMESTAMPDIFF(MINUTE, inbound.date_entered, MIN(outbound.date_entered)) as response_minutes,
                               inbound.parent_type,
                               inbound.parent_id
                           FROM notes inbound
                           LEFT JOIN notes outbound ON
                               outbound.parent_id = inbound.parent_id
                               AND outbound.parent_type = inbound.parent_type
                               AND (outbound.name LIKE '%📤%' OR outbound.name LIKE 'SMS to%')
                               AND outbound.date_entered > inbound.date_entered
                               AND outbound.deleted = 0
                           WHERE inbound.deleted = 0
                           AND (inbound.name LIKE '%📥%' OR inbound.name LIKE 'SMS from%')
                           AND inbound.date_entered >= '$dateFilter'
                           " . ($userId ? " AND inbound.assigned_user_id = '" . $db->quote($userId) . "'" : "") . "
                           GROUP BY inbound.id, inbound.date_entered, inbound.parent_type, inbound.parent_id
                           HAVING response_time IS NOT NULL";

        $result = $db->query($smsResponseSql);
        $smsResponseTimes = [];
        $totalSmsResponseTime = 0;
        $smsResponseCount = 0;

        while ($row = $db->fetchByAssoc($result)) {
            $smsResponseTimes[] = [
                'type' => 'sms',
                'inbound_time' => $row['inbound_time'],
                'response_time' => $row['response_time'],
                'response_minutes' => intval($row['response_minutes'])
            ];
            $totalSmsResponseTime += intval($row['response_minutes']);
            $smsResponseCount++;
        }

        // Calculate averages and stats
        $avgCallResponse = $callResponseCount > 0 ? round($totalCallResponseTime / $callResponseCount, 1) : 0;
        $avgSmsResponse = $smsResponseCount > 0 ? round($totalSmsResponseTime / $smsResponseCount, 1) : 0;
        $overallAvg = ($callResponseCount + $smsResponseCount) > 0
            ? round(($totalCallResponseTime + $totalSmsResponseTime) / ($callResponseCount + $smsResponseCount), 1)
            : 0;

        return [
            'summary' => [
                'avg_call_response_minutes' => $avgCallResponse,
                'avg_sms_response_minutes' => $avgSmsResponse,
                'avg_overall_response_minutes' => $overallAvg,
                'total_call_responses' => $callResponseCount,
                'total_sms_responses' => $smsResponseCount,
                'total_responses' => $callResponseCount + $smsResponseCount
            ],
            'call_details' => array_slice($callResponseTimes, 0, 50),
            'sms_details' => array_slice($smsResponseTimes, 0, 50),
            'response_time_buckets' => $this->calculateResponseTimeBuckets($callResponseTimes, $smsResponseTimes)
        ];
    }

    /**
     * Calculate response time distribution buckets
     */
    private function calculateResponseTimeBuckets($callTimes, $smsTimes)
    {
        $allTimes = array_merge($callTimes, $smsTimes);

        $buckets = [
            '0-15min' => 0,    // Excellent
            '15-60min' => 0,   // Good
            '1-4hr' => 0,      // Fair
            '4-24hr' => 0,     // Slow
            '24hr+' => 0       // Very Slow
        ];

        foreach ($allTimes as $item) {
            $minutes = $item['response_minutes'];

            if ($minutes <= 15) {
                $buckets['0-15min']++;
            } elseif ($minutes <= 60) {
                $buckets['15-60min']++;
            } elseif ($minutes <= 240) {
                $buckets['1-4hr']++;
            } elseif ($minutes <= 1440) {
                $buckets['4-24hr']++;
            } else {
                $buckets['24hr+']++;
            }
        }

        return $buckets;
    }

    /**
     * Get date filter based on period
     */
    private function getDateFilter($period)
    {
        switch ($period) {
            case 'today':
                return date('Y-m-d 00:00:00');
            case '7days':
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case '30days':
                return date('Y-m-d 00:00:00', strtotime('-30 days'));
            case '90days':
                return date('Y-m-d 00:00:00', strtotime('-90 days'));
            case 'year':
                return date('Y-01-01 00:00:00');
            default:
                return date('Y-m-d 00:00:00', strtotime('-30 days'));
        }
    }
}
