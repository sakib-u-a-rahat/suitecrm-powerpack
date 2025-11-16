<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class FunnelDashboard extends Basic {
    public $new_schema = true;
    public $module_dir = 'FunnelDashboard';
    public $object_name = 'FunnelDashboard';
    public $table_name = 'funnel_dashboard';
    public $importable = false;
    
    public $id;
    public $name;
    public $date_entered;
    public $date_modified;
    public $deleted;
    
    // Dashboard configuration fields
    public $category;
    public $funnel_config; // JSON field for funnel stages and rules

    public function __construct() {
        parent::__construct();
    }

    public function bean_implements($interface){
        switch($interface){
            case 'ACL': return true;
        }
        return false;
    }
    
    /**
     * Get funnel data by category
     */
    public static function getFunnelData($category = 'all', $dateFrom = null, $dateTo = null) {
        global $db;
        
        $data = array(
            'stages' => array(),
            'conversion_rates' => array(),
            'total_leads' => 0,
            'total_opportunities' => 0,
            'total_won' => 0,
        );
        
        // Build date filter
        $dateFilter = '';
        if ($dateFrom && $dateTo) {
            $dateFilter = " AND l.date_entered BETWEEN " . $db->quoted($dateFrom) . " AND " . $db->quoted($dateTo);
        }
        
        // Category filter
        $categoryFilter = '';
        if ($category !== 'all') {
            $categoryFilter = " AND l.lead_source = " . $db->quoted($category);
        }
        
        // Get leads by status
        $leadStages = self::getLeadsByStage($categoryFilter, $dateFilter);
        
        // Get opportunities by sales stage
        $oppStages = self::getOpportunitiesByStage($categoryFilter, $dateFilter);
        
        // Combine data
        $data['stages'] = array_merge($leadStages, $oppStages);
        $data['conversion_rates'] = self::calculateConversionRates($data['stages']);
        $data['total_leads'] = array_sum(array_column($leadStages, 'count'));
        $data['total_opportunities'] = array_sum(array_column($oppStages, 'count'));
        
        // Calculate won opportunities
        foreach ($oppStages as $stage) {
            if ($stage['stage'] === 'Closed Won') {
                $data['total_won'] = $stage['count'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get leads grouped by status
     */
    private static function getLeadsByStage($categoryFilter, $dateFilter) {
        global $db;
        
        $query = "SELECT l.status, COUNT(*) as count, SUM(o.amount) as total_value
                  FROM leads l
                  LEFT JOIN opportunities o ON l.id = o.parent_id AND o.parent_type = 'Leads'
                  WHERE l.deleted = 0 $categoryFilter $dateFilter
                  GROUP BY l.status
                  ORDER BY FIELD(l.status, 'New', 'Assigned', 'In Process', 'Converted', 'Recycled', 'Dead')";
        
        $result = $db->query($query);
        $stages = array();
        
        while ($row = $db->fetchByAssoc($result)) {
            $stages[] = array(
                'stage' => $row['status'],
                'type' => 'lead',
                'count' => (int)$row['count'],
                'value' => (float)($row['total_value'] ?? 0),
            );
        }
        
        return $stages;
    }
    
    /**
     * Get opportunities grouped by sales stage
     */
    private static function getOpportunitiesByStage($categoryFilter, $dateFilter) {
        global $db;
        
        $query = "SELECT o.sales_stage, COUNT(*) as count, SUM(o.amount) as total_value
                  FROM opportunities o
                  LEFT JOIN leads l ON o.parent_id = l.id AND o.parent_type = 'Leads'
                  WHERE o.deleted = 0 $categoryFilter $dateFilter
                  GROUP BY o.sales_stage
                  ORDER BY FIELD(o.sales_stage, 'Prospecting', 'Qualification', 'Needs Analysis', 
                                'Value Proposition', 'Id. Decision Makers', 'Perception Analysis', 
                                'Proposal/Price Quote', 'Negotiation/Review', 'Closed Won', 'Closed Lost')";
        
        $result = $db->query($query);
        $stages = array();
        
        while ($row = $db->fetchByAssoc($result)) {
            $stages[] = array(
                'stage' => $row['sales_stage'],
                'type' => 'opportunity',
                'count' => (int)$row['count'],
                'value' => (float)($row['total_value'] ?? 0),
            );
        }
        
        return $stages;
    }
    
    /**
     * Calculate conversion rates between stages
     */
    private static function calculateConversionRates($stages) {
        $rates = array();
        
        for ($i = 0; $i < count($stages) - 1; $i++) {
            $current = $stages[$i];
            $next = $stages[$i + 1];
            
            if ($current['count'] > 0) {
                $rate = ($next['count'] / $current['count']) * 100;
                $rates[] = array(
                    'from' => $current['stage'],
                    'to' => $next['stage'],
                    'rate' => round($rate, 2),
                );
            }
        }
        
        return $rates;
    }
    
    /**
     * Get available categories (lead sources)
     */
    public static function getCategories() {
        global $db, $app_list_strings;
        
        $query = "SELECT DISTINCT lead_source 
                  FROM leads 
                  WHERE deleted = 0 AND lead_source IS NOT NULL AND lead_source != ''
                  ORDER BY lead_source";
        
        $result = $db->query($query);
        $categories = array('all' => 'All Categories');
        
        while ($row = $db->fetchByAssoc($result)) {
            $source = $row['lead_source'];
            $categories[$source] = $app_list_strings['lead_source_dom'][$source] ?? $source;
        }
        
        return $categories;
    }
    
    /**
     * Get funnel velocity metrics
     */
    public static function getFunnelVelocity($category = 'all', $days = 30) {
        global $db;
        
        $dateFrom = date('Y-m-d', strtotime("-$days days"));
        $dateTo = date('Y-m-d');
        
        $categoryFilter = '';
        if ($category !== 'all') {
            $categoryFilter = " AND l.lead_source = " . $db->quoted($category);
        }
        
        // Average time in each stage
        $query = "SELECT 
                    l.status,
                    AVG(DATEDIFF(l.date_modified, l.date_entered)) as avg_days
                  FROM leads l
                  WHERE l.deleted = 0 
                  AND l.date_entered >= " . $db->quoted($dateFrom) . "
                  $categoryFilter
                  GROUP BY l.status";
        
        $result = $db->query($query);
        $velocity = array();
        
        while ($row = $db->fetchByAssoc($result)) {
            $velocity[] = array(
                'stage' => $row['status'],
                'avg_days' => round((float)$row['avg_days'], 1),
            );
        }
        
        return $velocity;
    }
    
    /**
     * Get top performing categories
     */
    public static function getTopCategories($limit = 5) {
        global $db;
        
        $query = "SELECT 
                    l.lead_source,
                    COUNT(DISTINCT l.id) as lead_count,
                    COUNT(DISTINCT CASE WHEN l.status = 'Converted' THEN l.id END) as converted_count,
                    COUNT(DISTINCT o.id) as opp_count,
                    SUM(CASE WHEN o.sales_stage = 'Closed Won' THEN o.amount ELSE 0 END) as won_amount
                  FROM leads l
                  LEFT JOIN opportunities o ON l.id = o.parent_id AND o.parent_type = 'Leads' AND o.deleted = 0
                  WHERE l.deleted = 0 
                  AND l.lead_source IS NOT NULL 
                  AND l.lead_source != ''
                  GROUP BY l.lead_source
                  ORDER BY won_amount DESC
                  LIMIT " . (int)$limit;
        
        $result = $db->query($query);
        $categories = array();
        
        while ($row = $db->fetchByAssoc($result)) {
            $conversionRate = $row['lead_count'] > 0 
                ? round(($row['converted_count'] / $row['lead_count']) * 100, 2) 
                : 0;
            
            $categories[] = array(
                'source' => $row['lead_source'],
                'lead_count' => (int)$row['lead_count'],
                'converted_count' => (int)$row['converted_count'],
                'opp_count' => (int)$row['opp_count'],
                'won_amount' => (float)$row['won_amount'],
                'conversion_rate' => $conversionRate,
            );
        }
        
        return $categories;
    }
}
