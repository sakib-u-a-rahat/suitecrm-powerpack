<?php
require_once('include/MVC/View/SugarView.php');

class FunnelDashboardViewSalesopsdashboard extends SugarView {
    public function display() {
        global $current_user;

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        // Get Sales Ops dashboard data
        $dashboardData = FunnelDashboard::getSalesOpsDashboard($dateFrom, $dateTo);

        echo $this->renderDashboard($dashboardData, $dateFrom, $dateTo);
    }

    private function renderDashboard($data, $dateFrom, $dateTo) {
        ob_start();
        ?>
        <style>
            .salesops-dashboard {
                padding: 20px;
                background: #f4f6f9;
                min-height: 100vh;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .dashboard-header {
                background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
                color: white;
                padding: 25px 30px;
                border-radius: 12px;
                margin-bottom: 25px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .dashboard-header h1 { margin: 0; font-size: 28px; font-weight: 600; }
            .dashboard-header .subtitle { opacity: 0.8; margin-top: 5px; font-size: 14px; }
            .filter-bar {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .filter-bar input, .filter-bar select {
                padding: 10px 15px;
                border: none;
                border-radius: 8px;
                background: rgba(255,255,255,0.1);
                color: white;
                font-size: 14px;
            }
            .filter-bar button {
                padding: 10px 20px;
                background: #e94560;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
            }

            .alert-banner {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .alert-banner.critical {
                background: #fee2e2;
                border-left-color: #dc2626;
            }
            .alert-icon { font-size: 24px; }
            .alert-text .title { font-weight: 600; margin-bottom: 3px; }
            .alert-text .message { font-size: 13px; color: #666; }

            .metrics-grid {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 15px;
                margin-bottom: 25px;
            }
            .metric-card {
                background: white;
                padding: 20px;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }
            .metric-card .label {
                font-size: 12px;
                color: #666;
                text-transform: uppercase;
                margin-bottom: 8px;
            }
            .metric-card .value {
                font-size: 28px;
                font-weight: 700;
                color: #1a1a2e;
            }
            .metric-card .indicator {
                font-size: 12px;
                margin-top: 5px;
            }
            .metric-card .indicator.warning { color: #f59e0b; }
            .metric-card .indicator.danger { color: #dc2626; }
            .metric-card .indicator.success { color: #10b981; }

            .dashboard-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 20px;
            }
            .card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }
            .card.full-width { grid-column: 1 / -1; }
            .card-header {
                padding: 18px 20px;
                border-bottom: 1px solid #eee;
                font-size: 15px;
                font-weight: 600;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .card-body { padding: 20px; }

            .bdm-table {
                width: 100%;
                border-collapse: collapse;
            }
            .bdm-table th, .bdm-table td {
                padding: 12px 15px;
                text-align: left;
                border-bottom: 1px solid #eee;
            }
            .bdm-table th {
                font-size: 11px;
                text-transform: uppercase;
                color: #666;
                font-weight: 600;
                background: #f8f9fa;
            }
            .bdm-table tr:hover { background: #f8f9fa; }
            .status-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }
            .status-badge.on-track { background: #d1fae5; color: #065f46; }
            .status-badge.at-risk { background: #fef3c7; color: #92400e; }
            .status-badge.behind { background: #fee2e2; color: #991b1b; }

            .stalled-grid {
                display: grid;
                gap: 10px;
                max-height: 350px;
                overflow-y: auto;
            }
            .stalled-row {
                display: grid;
                grid-template-columns: 2fr 1fr 1fr 1fr 80px;
                align-items: center;
                padding: 12px 15px;
                background: #f8f9fa;
                border-radius: 8px;
                font-size: 13px;
            }
            .stalled-row:hover { background: #f0f0f0; }
            .stalled-row .lead-name a {
                color: #1a1a2e;
                text-decoration: none;
                font-weight: 500;
            }
            .stalled-row .lead-name a:hover { color: #4361ee; }
            .days-badge {
                background: #fee2e2;
                color: #dc2626;
                padding: 4px 8px;
                border-radius: 8px;
                font-weight: 600;
                text-align: center;
            }
            .days-badge.warning { background: #fef3c7; color: #92400e; }

            .underperformer-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 15px;
                background: #fef2f2;
                border-radius: 8px;
                margin-bottom: 10px;
            }
            .underperformer-item .info .name { font-weight: 600; }
            .underperformer-item .info .detail { font-size: 12px; color: #666; }
            .underperformer-item .achievement {
                font-weight: 700;
                color: #dc2626;
            }

            .package-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            .package-card {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
            }
            .package-card .name { font-weight: 600; margin-bottom: 10px; }
            .package-card .stat { font-size: 13px; margin-bottom: 5px; }
            .package-card .stat .value { font-weight: 600; color: #4361ee; }
        </style>

        <div class="salesops-dashboard">
            <div class="dashboard-header">
                <div>
                    <h1>Sales Operations Dashboard</h1>
                    <div class="subtitle">Team Performance & Operations Management</div>
                </div>
                <form method="GET" class="filter-bar">
                    <input type="hidden" name="module" value="FunnelDashboard">
                    <input type="hidden" name="action" value="salesopsdashboard">
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                    <button type="submit">Apply</button>
                </form>
            </div>

            <!-- Alert Banners -->
            <?php
            $stalledCount = count($data['stalled_leads']);
            $overdueCount = $data['follow_up_metrics']['overdue'];
            $underperformerCount = count($data['underperformers']);

            if ($stalledCount > 10 || $overdueCount > 20):
            ?>
            <div class="alert-banner critical">
                <span class="alert-icon">!</span>
                <div class="alert-text">
                    <div class="title">Attention Required</div>
                    <div class="message"><?php echo $stalledCount; ?> stalled leads and <?php echo $overdueCount; ?> overdue follow-ups need immediate action.</div>
                </div>
            </div>
            <?php elseif ($underperformerCount > 0): ?>
            <div class="alert-banner">
                <span class="alert-icon">!</span>
                <div class="alert-text">
                    <div class="title">Performance Alert</div>
                    <div class="message"><?php echo $underperformerCount; ?> BDM(s) are below target threshold.</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Top Metrics -->
            <div class="metrics-grid">
                <?php
                $totalLeads = 0;
                $totalRevenue = 0;
                foreach ($data['funnel_comparison'] as $f) {
                    $totalLeads += $f['total_leads'];
                    $totalRevenue += $f['total_revenue'];
                }
                ?>
                <div class="metric-card">
                    <div class="label">Total Leads</div>
                    <div class="value"><?php echo number_format($totalLeads); ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Total Revenue</div>
                    <div class="value">$<?php echo number_format($totalRevenue); ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Demos Completed</div>
                    <div class="value"><?php echo $data['demos_metrics']['demos_completed']; ?></div>
                    <div class="indicator <?php echo $data['demos_metrics']['completion_rate'] < 70 ? 'warning' : 'success'; ?>">
                        <?php echo $data['demos_metrics']['completion_rate']; ?>% completion rate
                    </div>
                </div>
                <div class="metric-card">
                    <div class="label">Stalled Leads</div>
                    <div class="value"><?php echo $stalledCount; ?></div>
                    <div class="indicator <?php echo $stalledCount > 10 ? 'danger' : 'warning'; ?>">
                        Needs attention
                    </div>
                </div>
                <div class="metric-card">
                    <div class="label">Overdue Follow-ups</div>
                    <div class="value"><?php echo $overdueCount; ?></div>
                    <div class="indicator <?php echo $overdueCount > 20 ? 'danger' : 'warning'; ?>">
                        <?php echo $data['follow_up_metrics']['completion_rate']; ?>% on-time rate
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- BDM Performance Table -->
                <div class="card full-width">
                    <div class="card-header">
                        <span>BDM Performance</span>
                        <a href="index.php?module=SalesTargets&action=index" style="font-size: 12px; color: #4361ee;">View All Targets</a>
                    </div>
                    <div class="card-body">
                        <table class="bdm-table">
                            <thead>
                                <tr>
                                    <th>BDM Name</th>
                                    <th>Deals</th>
                                    <th>Revenue</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['revenue_by_bdm'] as $bdm):
                                    // Determine status based on performance (simplified)
                                    $avgRevenue = $totalRevenue / max(count($data['revenue_by_bdm']), 1);
                                    if ($bdm['total_revenue'] >= $avgRevenue) {
                                        $status = 'on-track';
                                        $statusLabel = 'On Track';
                                    } elseif ($bdm['total_revenue'] >= $avgRevenue * 0.7) {
                                        $status = 'at-risk';
                                        $statusLabel = 'At Risk';
                                    } else {
                                        $status = 'behind';
                                        $statusLabel = 'Behind';
                                    }
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($bdm['bdm_name']); ?></strong></td>
                                    <td><?php echo $bdm['deal_count']; ?></td>
                                    <td>$<?php echo number_format($bdm['total_revenue']); ?></td>
                                    <td>$<?php echo number_format($bdm['total_commission']); ?></td>
                                    <td><span class="status-badge <?php echo $status; ?>"><?php echo $statusLabel; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Stalled Leads -->
                <div class="card">
                    <div class="card-header">
                        <span>Stalled Leads (7+ Days)</span>
                        <span style="font-size: 12px; color: #dc2626;"><?php echo $stalledCount; ?> leads</span>
                    </div>
                    <div class="card-body">
                        <div class="stalled-grid">
                            <?php if (empty($data['stalled_leads'])): ?>
                                <p style="text-align: center; color: #10b981; padding: 20px;">No stalled leads!</p>
                            <?php else: ?>
                                <?php foreach (array_slice($data['stalled_leads'], 0, 15) as $lead):
                                    $daysClass = $lead['days_stalled'] > 14 ? '' : 'warning';
                                ?>
                                <div class="stalled-row">
                                    <div class="lead-name">
                                        <a href="index.php?module=Leads&action=DetailView&record=<?php echo $lead['id']; ?>">
                                            <?php echo htmlspecialchars($lead['name']); ?>
                                        </a>
                                    </div>
                                    <div><?php echo htmlspecialchars($lead['bdm_name']); ?></div>
                                    <div><?php echo htmlspecialchars(str_replace('_', ' ', $lead['funnel_type'] ?? 'N/A')); ?></div>
                                    <div><?php echo htmlspecialchars(str_replace('_', ' ', $lead['pipeline_stage'] ?? 'N/A')); ?></div>
                                    <div class="days-badge <?php echo $daysClass; ?>"><?php echo $lead['days_stalled']; ?>d</div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Underperformers -->
                <div class="card">
                    <div class="card-header">
                        <span>Underperforming BDMs (&lt;70%)</span>
                        <span style="font-size: 12px; color: #f59e0b;"><?php echo $underperformerCount; ?> BDMs</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($data['underperformers'])): ?>
                            <p style="text-align: center; color: #10b981; padding: 20px;">All BDMs meeting targets!</p>
                        <?php else: ?>
                            <?php foreach ($data['underperformers'] as $up): ?>
                            <div class="underperformer-item">
                                <div class="info">
                                    <div class="name"><?php echo htmlspecialchars($up['bdm_name']); ?></div>
                                    <div class="detail">
                                        Target: $<?php echo number_format($up['revenue_target']); ?> |
                                        Actual: $<?php echo number_format($up['revenue_actual']); ?>
                                    </div>
                                </div>
                                <div class="achievement"><?php echo $up['achievement_pct']; ?>%</div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Package Sales -->
            <?php if (!empty($data['package_sales'])): ?>
            <div class="card">
                <div class="card-header">Package Sales Summary</div>
                <div class="card-body">
                    <div class="package-grid">
                        <?php foreach (array_slice($data['package_sales'], 0, 6) as $pkg): ?>
                        <div class="package-card">
                            <div class="name"><?php echo htmlspecialchars($pkg['package_name']); ?></div>
                            <div class="stat">Sales: <span class="value"><?php echo $pkg['sales_count']; ?></span></div>
                            <div class="stat">Revenue: <span class="value">$<?php echo number_format($pkg['total_revenue']); ?></span></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
