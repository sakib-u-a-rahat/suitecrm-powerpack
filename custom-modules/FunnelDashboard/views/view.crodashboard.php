<?php
require_once('include/MVC/View/SugarView.php');

class FunnelDashboardViewCrodashboard extends SugarView {
    public function display() {
        global $current_user;

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        // Get CRO dashboard data
        $dashboardData = FunnelDashboard::getCRODashboard($dateFrom, $dateTo);

        echo $this->renderDashboard($dashboardData, $dateFrom, $dateTo);
    }

    private function renderDashboard($data, $dateFrom, $dateTo) {
        ob_start();
        ?>
        <style>
            .cro-dashboard {
                padding: 20px;
                background: #f4f6f9;
                min-height: 100vh;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .dashboard-header {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
                color: white;
                padding: 25px 30px;
                border-radius: 12px;
                margin-bottom: 25px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .dashboard-header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
            }
            .dashboard-header .subtitle {
                opacity: 0.8;
                margin-top: 5px;
                font-size: 14px;
            }
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
            .filter-bar input::placeholder { color: rgba(255,255,255,0.6); }
            .filter-bar button {
                padding: 10px 20px;
                background: #4361ee;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
            }
            .filter-bar button:hover { background: #3a56d4; }

            .metrics-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
                margin-bottom: 25px;
            }
            .metric-card {
                background: white;
                padding: 25px;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }
            .metric-card .label {
                font-size: 13px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 8px;
            }
            .metric-card .value {
                font-size: 32px;
                font-weight: 700;
                color: #1a1a2e;
            }
            .metric-card .change {
                font-size: 13px;
                margin-top: 8px;
            }
            .metric-card .change.positive { color: #10b981; }
            .metric-card .change.negative { color: #ef4444; }

            .dashboard-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 25px;
                margin-bottom: 25px;
            }
            .card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                overflow: hidden;
            }
            .card-header {
                padding: 20px 25px;
                border-bottom: 1px solid #eee;
                font-size: 16px;
                font-weight: 600;
                color: #1a1a2e;
            }
            .card-body {
                padding: 20px 25px;
            }

            .funnel-comparison {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
            .funnel-item {
                text-align: center;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .funnel-item .name {
                font-weight: 600;
                margin-bottom: 15px;
                color: #1a1a2e;
            }
            .funnel-item .stats {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .funnel-item .stat {
                display: flex;
                justify-content: space-between;
                font-size: 13px;
            }
            .funnel-item .stat .label { color: #666; }
            .funnel-item .stat .value { font-weight: 600; }

            .leaderboard-table {
                width: 100%;
                border-collapse: collapse;
            }
            .leaderboard-table th,
            .leaderboard-table td {
                padding: 12px 15px;
                text-align: left;
                border-bottom: 1px solid #eee;
            }
            .leaderboard-table th {
                font-size: 12px;
                text-transform: uppercase;
                color: #666;
                font-weight: 600;
            }
            .leaderboard-table tr:hover {
                background: #f8f9fa;
            }
            .rank-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                font-weight: 600;
                font-size: 12px;
            }
            .rank-1 { background: #ffd700; color: #1a1a2e; }
            .rank-2 { background: #c0c0c0; color: #1a1a2e; }
            .rank-3 { background: #cd7f32; color: white; }
            .rank-default { background: #e0e0e0; color: #666; }

            .stalled-list {
                max-height: 300px;
                overflow-y: auto;
            }
            .stalled-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px solid #eee;
            }
            .stalled-item:last-child { border-bottom: none; }
            .stalled-info .name { font-weight: 500; }
            .stalled-info .details { font-size: 12px; color: #666; }
            .stalled-days {
                background: #fee2e2;
                color: #dc2626;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }

            .velocity-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .velocity-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .velocity-item .stage { font-size: 13px; }
            .velocity-item .days {
                font-weight: 600;
                color: #4361ee;
            }
        </style>

        <div class="cro-dashboard">
            <div class="dashboard-header">
                <div>
                    <h1>CRO Dashboard</h1>
                    <div class="subtitle">Strategic Overview - All Funnels</div>
                </div>
                <form method="GET" class="filter-bar">
                    <input type="hidden" name="module" value="FunnelDashboard">
                    <input type="hidden" name="action" value="crodashboard">
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                    <button type="submit">Apply</button>
                </form>
            </div>

            <!-- Top Metrics -->
            <div class="metrics-grid">
                <?php
                $totalLeads = 0;
                $totalWon = 0;
                $totalRevenue = 0;
                foreach ($data['funnel_comparison'] as $funnel) {
                    $totalLeads += $funnel['total_leads'];
                    $totalWon += $funnel['total_won'];
                    $totalRevenue += $funnel['total_revenue'];
                }
                $avgWinRate = $totalLeads > 0 ? round(($totalWon / $totalLeads) * 100, 1) : 0;
                ?>
                <div class="metric-card">
                    <div class="label">Total Leads</div>
                    <div class="value"><?php echo number_format($totalLeads); ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Deals Won</div>
                    <div class="value"><?php echo number_format($totalWon); ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Total Revenue</div>
                    <div class="value">$<?php echo number_format($totalRevenue); ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Win Rate</div>
                    <div class="value"><?php echo $avgWinRate; ?>%</div>
                </div>
            </div>

            <!-- Funnel Comparison -->
            <div class="card" style="margin-bottom: 25px;">
                <div class="card-header">Funnel Performance Comparison</div>
                <div class="card-body">
                    <div class="funnel-comparison">
                        <?php foreach ($data['funnel_comparison'] as $funnel): ?>
                        <div class="funnel-item">
                            <div class="name"><?php echo htmlspecialchars($funnel['funnel_label']); ?></div>
                            <div class="stats">
                                <div class="stat">
                                    <span class="label">Leads</span>
                                    <span class="value"><?php echo number_format($funnel['total_leads']); ?></span>
                                </div>
                                <div class="stat">
                                    <span class="label">Won</span>
                                    <span class="value"><?php echo number_format($funnel['total_won']); ?></span>
                                </div>
                                <div class="stat">
                                    <span class="label">Revenue</span>
                                    <span class="value">$<?php echo number_format($funnel['total_revenue']); ?></span>
                                </div>
                                <div class="stat">
                                    <span class="label">Win Rate</span>
                                    <span class="value"><?php echo $funnel['win_rate']; ?>%</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- BDM Leaderboard -->
                <div class="card">
                    <div class="card-header">BDM Leaderboard - Revenue</div>
                    <div class="card-body">
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>BDM</th>
                                    <th>Deals</th>
                                    <th>Revenue</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                foreach ($data['revenue_by_bdm'] as $bdm):
                                    $rankClass = $rank <= 3 ? "rank-$rank" : 'rank-default';
                                ?>
                                <tr>
                                    <td><span class="rank-badge <?php echo $rankClass; ?>"><?php echo $rank++; ?></span></td>
                                    <td><?php echo htmlspecialchars($bdm['bdm_name']); ?></td>
                                    <td><?php echo $bdm['deal_count']; ?></td>
                                    <td>$<?php echo number_format($bdm['total_revenue']); ?></td>
                                    <td>$<?php echo number_format($bdm['total_commission']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pipeline Velocity -->
                <div class="card">
                    <div class="card-header">Pipeline Velocity</div>
                    <div class="card-body">
                        <div class="velocity-list">
                            <?php foreach ($data['pipeline_velocity'] as $stage): ?>
                            <div class="velocity-item">
                                <span class="stage"><?php echo htmlspecialchars(str_replace('_', ' ', $stage['stage'])); ?></span>
                                <span class="days"><?php echo $stage['avg_days']; ?> days</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- KPI Summary -->
                <div class="card">
                    <div class="card-header">Key Activity Metrics</div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div class="funnel-item">
                                <div class="name">Demos</div>
                                <div class="stats">
                                    <div class="stat">
                                        <span class="label">Scheduled</span>
                                        <span class="value"><?php echo $data['demos_metrics']['demos_scheduled']; ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="label">Completed</span>
                                        <span class="value"><?php echo $data['demos_metrics']['demos_completed']; ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="label">Rate</span>
                                        <span class="value"><?php echo $data['demos_metrics']['completion_rate']; ?>%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="funnel-item">
                                <div class="name">Follow-ups</div>
                                <div class="stats">
                                    <div class="stat">
                                        <span class="label">Due</span>
                                        <span class="value"><?php echo $data['follow_up_metrics']['follow_ups_due']; ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="label">Completed</span>
                                        <span class="value"><?php echo $data['follow_up_metrics']['follow_ups_completed']; ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="label">Overdue</span>
                                        <span class="value" style="color: #dc2626;"><?php echo $data['follow_up_metrics']['overdue']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stalled Leads Alert -->
                <div class="card">
                    <div class="card-header">Stalled Leads Alert</div>
                    <div class="card-body">
                        <div class="stalled-list">
                            <?php if (empty($data['stalled_leads'])): ?>
                                <p style="color: #10b981; text-align: center; padding: 20px;">No stalled leads!</p>
                            <?php else: ?>
                                <?php foreach ($data['stalled_leads'] as $lead): ?>
                                <div class="stalled-item">
                                    <div class="stalled-info">
                                        <div class="name">
                                            <a href="index.php?module=Leads&action=DetailView&record=<?php echo $lead['id']; ?>">
                                                <?php echo htmlspecialchars($lead['name']); ?>
                                            </a>
                                        </div>
                                        <div class="details">
                                            <?php echo htmlspecialchars($lead['bdm_name']); ?> |
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $lead['pipeline_stage'])); ?>
                                        </div>
                                    </div>
                                    <span class="stalled-days"><?php echo $lead['days_stalled']; ?> days</span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
