<?php
require_once('include/MVC/View/SugarView.php');

class FunnelDashboardViewDashboard extends SugarView {
    public function display() {
        $category = $_GET['category'] ?? 'all';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Get data
        $funnelData = FunnelDashboard::getFunnelData($category, $dateFrom, $dateTo);
        $categories = FunnelDashboard::getCategories();
        $velocity = FunnelDashboard::getFunnelVelocity($category, 30);
        $topCategories = FunnelDashboard::getTopCategories(5);
        
        echo $this->renderDashboard($funnelData, $categories, $velocity, $topCategories, $category, $dateFrom, $dateTo);
    }
    
    private function renderDashboard($funnelData, $categories, $velocity, $topCategories, $selectedCategory, $dateFrom, $dateTo) {
        ob_start();
        ?>
        <style>
            .funnel-dashboard {
                padding: 20px;
                background: #f4f6f9;
                min-height: 100vh;
            }
            .dashboard-header {
                background: white;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .dashboard-header h1 {
                margin: 0 0 20px 0;
                color: #333;
            }
            .filter-bar {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
            }
            .filter-bar select,
            .filter-bar input {
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .filter-bar button {
                padding: 8px 20px;
                background: #0070d2;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .filter-bar button:hover {
                background: #005fb2;
            }
            .metrics-row {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 20px;
            }
            .metric-card {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .metric-card .label {
                font-size: 14px;
                color: #666;
                margin-bottom: 10px;
            }
            .metric-card .value {
                font-size: 32px;
                font-weight: bold;
                color: #0070d2;
            }
            .metric-card .subvalue {
                font-size: 14px;
                color: #999;
                margin-top: 5px;
            }
            .dashboard-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 20px;
                margin-bottom: 20px;
            }
            .funnel-chart {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .funnel-chart h3 {
                margin: 0 0 20px 0;
                color: #333;
            }
            .funnel-stage {
                margin-bottom: 15px;
                position: relative;
            }
            .funnel-stage-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .funnel-bar {
                height: 40px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 4px;
                display: flex;
                align-items: center;
                padding: 0 15px;
                color: white;
                font-weight: bold;
                position: relative;
                transition: all 0.3s ease;
            }
            .funnel-bar:hover {
                transform: translateX(5px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            .funnel-bar.lead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .funnel-bar.opportunity { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
            .funnel-bar.won { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
            .conversion-arrow {
                text-align: center;
                color: #666;
                font-size: 12px;
                margin: 5px 0;
            }
            .velocity-panel {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .velocity-panel h3 {
                margin: 0 0 20px 0;
                color: #333;
            }
            .velocity-item {
                display: flex;
                justify-content: space-between;
                padding: 10px;
                border-bottom: 1px solid #eee;
            }
            .velocity-item:last-child {
                border-bottom: none;
            }
            .top-categories {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                grid-column: 1 / -1;
            }
            .top-categories h3 {
                margin: 0 0 20px 0;
                color: #333;
            }
            .category-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px;
                border-bottom: 1px solid #eee;
            }
            .category-row:hover {
                background: #f9f9f9;
            }
            .category-name {
                font-weight: bold;
                flex: 1;
            }
            .category-stats {
                display: flex;
                gap: 30px;
            }
            .category-stat {
                text-align: center;
            }
            .category-stat .label {
                font-size: 11px;
                color: #666;
                text-transform: uppercase;
            }
            .category-stat .value {
                font-size: 18px;
                font-weight: bold;
                color: #0070d2;
            }
        </style>
        
        <div class="funnel-dashboard">
            <div class="dashboard-header">
                <h1>Sales Funnel Dashboard</h1>
                <form method="GET" class="filter-bar">
                    <input type="hidden" name="module" value="FunnelDashboard">
                    <input type="hidden" name="action" value="dashboard">
                    
                    <select name="category">
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $key === $selectedCategory ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                    
                    <button type="submit">Apply Filters</button>
                </form>
            </div>
            
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="label">Total Leads</div>
                    <div class="value"><?php echo number_format($funnelData['total_leads']); ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Total Opportunities</div>
                    <div class="value"><?php echo number_format($funnelData['total_opportunities']); ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Won Deals</div>
                    <div class="value"><?php echo number_format($funnelData['total_won']); ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Conversion Rate</div>
                    <div class="value">
                        <?php 
                        $convRate = $funnelData['total_leads'] > 0 
                            ? round(($funnelData['total_won'] / $funnelData['total_leads']) * 100, 2) 
                            : 0;
                        echo $convRate . '%';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="funnel-chart">
                    <h3>Sales Funnel Stages</h3>
                    <?php 
                    $maxCount = 0;
                    foreach ($funnelData['stages'] as $stage) {
                        if ($stage['count'] > $maxCount) $maxCount = $stage['count'];
                    }
                    
                    foreach ($funnelData['stages'] as $index => $stage): 
                        $width = $maxCount > 0 ? ($stage['count'] / $maxCount) * 100 : 0;
                        $typeClass = $stage['type'];
                        if ($stage['stage'] === 'Closed Won') $typeClass = 'won';
                    ?>
                        <div class="funnel-stage">
                            <div class="funnel-stage-header">
                                <span><?php echo htmlspecialchars($stage['stage']); ?></span>
                                <span><?php echo number_format($stage['count']); ?> | $<?php echo number_format($stage['value']); ?></span>
                            </div>
                            <div class="funnel-bar <?php echo $typeClass; ?>" style="width: <?php echo $width; ?>%;">
                                <?php echo number_format($stage['count']); ?> records
                            </div>
                        </div>
                        <?php if (isset($funnelData['conversion_rates'][$index])): ?>
                            <div class="conversion-arrow">
                                ↓ <?php echo $funnelData['conversion_rates'][$index]['rate']; ?>% conversion
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="velocity-panel">
                    <h3>Funnel Velocity</h3>
                    <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Average days in each stage</p>
                    <?php foreach ($velocity as $item): ?>
                        <div class="velocity-item">
                            <span><?php echo htmlspecialchars($item['stage']); ?></span>
                            <strong><?php echo $item['avg_days']; ?> days</strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="top-categories">
                <h3>Top Performing Categories</h3>
                <?php foreach ($topCategories as $cat): ?>
                    <div class="category-row">
                        <div class="category-name"><?php echo htmlspecialchars($cat['source']); ?></div>
                        <div class="category-stats">
                            <div class="category-stat">
                                <div class="label">Leads</div>
                                <div class="value"><?php echo number_format($cat['lead_count']); ?></div>
                            </div>
                            <div class="category-stat">
                                <div class="label">Converted</div>
                                <div class="value"><?php echo number_format($cat['converted_count']); ?></div>
                            </div>
                            <div class="category-stat">
                                <div class="label">Conv. Rate</div>
                                <div class="value"><?php echo $cat['conversion_rate']; ?>%</div>
                            </div>
                            <div class="category-stat">
                                <div class="label">Revenue</div>
                                <div class="value">$<?php echo number_format($cat['won_amount']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
