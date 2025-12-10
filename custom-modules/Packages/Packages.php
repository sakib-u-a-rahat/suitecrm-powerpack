<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class Packages extends Basic {
    public $new_schema = true;
    public $module_dir = 'Packages';
    public $object_name = 'Packages';
    public $table_name = 'packages';
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

    // Package specific fields
    public $package_code;
    public $package_type; // Realtors, Senior_Living, Home_Care
    public $price;
    public $billing_frequency; // one-time, monthly, annual
    public $commission_rate;
    public $commission_flat;
    public $features; // JSON
    public $is_active;

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
     * Calculate commission for this package
     */
    public function calculateCommission($saleAmount = null) {
        $amount = $saleAmount ?: $this->price;

        // If flat commission is set, use it
        if ($this->commission_flat > 0) {
            return (float)$this->commission_flat;
        }

        // Otherwise calculate from rate
        $rate = (float)($this->commission_rate ?: 5); // Default 5%
        return round($amount * ($rate / 100), 2);
    }

    /**
     * Get packages by funnel type
     */
    public static function getPackagesByType($packageType = null, $activeOnly = true) {
        global $db;

        $query = "SELECT * FROM packages WHERE deleted = 0";

        if ($activeOnly) {
            $query .= " AND is_active = 1";
        }

        if ($packageType && $packageType !== 'all') {
            $query .= " AND (package_type = " . $db->quoted($packageType) . " OR package_type IS NULL OR package_type = '')";
        }

        $query .= " ORDER BY package_type, name";

        $result = $db->query($query);
        $packages = array();

        while ($row = $db->fetchByAssoc($result)) {
            $row['features_array'] = !empty($row['features']) ? json_decode($row['features'], true) : array();
            $packages[] = $row;
        }

        return $packages;
    }

    /**
     * Get all active packages for dropdown
     */
    public static function getActivePackages() {
        global $db;

        $query = "SELECT id, name, package_type, price
                  FROM packages
                  WHERE deleted = 0 AND is_active = 1
                  ORDER BY package_type, name";

        $result = $db->query($query);
        $packages = array('' => '');

        while ($row = $db->fetchByAssoc($result)) {
            $label = $row['name'];
            if ($row['package_type']) {
                $label .= ' (' . $row['package_type'] . ')';
            }
            if ($row['price'] > 0) {
                $label .= ' - $' . number_format($row['price'], 2);
            }
            $packages[$row['id']] = $label;
        }

        return $packages;
    }

    /**
     * Get package sales summary by funnel/BDM
     */
    public static function getPackageSalesSummary($funnelType = null, $userId = null, $dateFrom = null, $dateTo = null) {
        global $db;

        $dateFrom = $dateFrom ?: date('Y-m-01');
        $dateTo = $dateTo ?: date('Y-m-d');

        $query = "SELECT
                    p.id as package_id,
                    p.name as package_name,
                    p.package_type,
                    p.price,
                    COUNT(o.id) as sales_count,
                    SUM(o.amount) as total_revenue,
                    SUM(CASE WHEN p.commission_flat > 0 THEN p.commission_flat
                             ELSE o.amount * (p.commission_rate / 100) END) as total_commission
                  FROM packages p
                  LEFT JOIN opportunities o ON o.package_id_c = p.id
                    AND o.sales_stage = 'Closed Won'
                    AND o.deleted = 0
                    AND o.date_closed BETWEEN " . $db->quoted($dateFrom) . " AND " . $db->quoted($dateTo);

        if ($userId) {
            $query .= " AND o.assigned_user_id = " . $db->quoted($userId);
        }

        $query .= " WHERE p.deleted = 0 AND p.is_active = 1";

        if ($funnelType && $funnelType !== 'all') {
            $query .= " AND (p.package_type = " . $db->quoted($funnelType) . " OR p.package_type IS NULL)";
        }

        $query .= " GROUP BY p.id, p.name, p.package_type, p.price
                    ORDER BY total_revenue DESC";

        $result = $db->query($query);
        $summary = array();

        while ($row = $db->fetchByAssoc($result)) {
            $summary[] = array(
                'package_id' => $row['package_id'],
                'package_name' => $row['package_name'],
                'package_type' => $row['package_type'],
                'price' => (float)$row['price'],
                'sales_count' => (int)$row['sales_count'],
                'total_revenue' => (float)($row['total_revenue'] ?? 0),
                'total_commission' => (float)($row['total_commission'] ?? 0),
            );
        }

        return $summary;
    }

    /**
     * Get top selling packages
     */
    public static function getTopSellingPackages($limit = 5, $dateFrom = null, $dateTo = null) {
        global $db;

        $dateFrom = $dateFrom ?: date('Y-m-01');
        $dateTo = $dateTo ?: date('Y-m-d');

        $query = "SELECT
                    p.id,
                    p.name,
                    p.package_type,
                    COUNT(o.id) as sales_count,
                    SUM(o.amount) as total_revenue
                  FROM packages p
                  INNER JOIN opportunities o ON o.package_id_c = p.id
                  WHERE p.deleted = 0
                  AND o.deleted = 0
                  AND o.sales_stage = 'Closed Won'
                  AND o.date_closed BETWEEN " . $db->quoted($dateFrom) . " AND " . $db->quoted($dateTo) . "
                  GROUP BY p.id, p.name, p.package_type
                  ORDER BY sales_count DESC
                  LIMIT " . (int)$limit;

        $result = $db->query($query);
        $packages = array();

        while ($row = $db->fetchByAssoc($result)) {
            $packages[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'package_type' => $row['package_type'],
                'sales_count' => (int)$row['sales_count'],
                'total_revenue' => (float)$row['total_revenue'],
            );
        }

        return $packages;
    }
}
