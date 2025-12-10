<?php
require_once('include/MVC/View/SugarView.php');

class FunnelDashboardViewBdmdashboard extends SugarView {
    public function display() {
        global $current_user;

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        // Get BDM dashboard data for current user
        $dashboardData = FunnelDashboard::getBDMDashboard($current_user->id, $dateFrom, $dateTo);

        echo $this->renderDashboard($dashboardData, $dateFrom, $dateTo, $current_user);
    }

    private function renderDashboard($data, $dateFrom, $dateTo, $user) {
        ob_start();
        ?>
        <style>
            .bdm-dashboard {
                padding: 20px;
                background: #f4f6f9;
                min-height: 100vh;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .dashboard-header {
                background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
                color: white;
                padding: 25px 30px;
                border-radius: 12px;
                margin-bottom: 25px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .dashboard-header h1 { margin: 0; font-size: 28px; font-weight: 600; }
            .dashboard-header .welcome {
                opacity: 0.9;
                margin-top: 5px;
                font-size: 14px;
            }
            .filter-bar {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .filter-bar input {
                padding: 10px 15px;
                border: none;
                border-radius: 8px;
                background: rgba(255,255,255,0.1);
                color: white;
                font-size: 14px;
            }
            .filter-bar button {
                padding: 10px 20px;
                background: #48bb78;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
            }

            .summary-cards {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
                margin-bottom: 25px;
            }
            .summary-card {
                background: white;
                padding: 25px;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                position: relative;
                overflow: hidden;
            }
            .summary-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 4px;
                height: 100%;
            }
            .summary-card.revenue::before { background: #48bb78; }
            .summary-card.deals::before { background: #4299e1; }
            .summary-card.commission::before { background: #ed8936; }
            .summary-card.rank::before { background: #9f7aea; }
            .summary-card .label {
                font-size: 13px;
                color: #666;
                text-transform: uppercase;
                margin-bottom: 10px;
            }
            .summary-card .value {
                font-size: 36px;
                font-weight: 700;
                color: #1a1a2e;
            }
            .summary-card .subtext {
                font-size: 12px;
                color: #888;
                margin-top: 8px;
            }

            .dashboard-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 20px;
                margin-bottom: 20px;
            }
            .card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }
            .card-header {
                padding: 18px 20px;
                border-bottom: 1px solid #eee;
                font-size: 16px;
                font-weight: 600;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .card-body { padding: 20px; }

            .pipeline-stages {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .pipeline-stage {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .pipeline-stage .stage-name {
                width: 140px;
                font-size: 13px;
                color: #333;
            }
            .pipeline-stage .stage-bar-container {
                flex: 1;
                height: 24px;
                background: #e2e8f0;
                border-radius: 4px;
                overflow: hidden;
            }
            .pipeline-stage .stage-bar {
                height: 100%;
                background: linear-gradient(90deg, #48bb78 0%, #38a169 100%);
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                padding-right: 8px;
                color: white;
                font-size: 11px;
                font-weight: 600;
                min-width: 30px;
            }
            .pipeline-stage .stage-count {
                width: 50px;
                text-align: right;
                font-weight: 600;
                color: #1a1a2e;
            }
            .pipeline-stage .stage-value {
                width: 80px;
                text-align: right;
                font-size: 12px;
                color: #666;
            }

            .target-progress {
                margin-bottom: 20px;
            }
            .target-item {
                margin-bottom: 20px;
            }
            .target-item .header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-size: 13px;
            }
            .target-item .header .name { font-weight: 500; }
            .target-item .header .values { color: #666; }
            .target-item .progress-bar {
                height: 10px;
                background: #e2e8f0;
                border-radius: 5px;
                overflow: hidden;
            }
            .target-item .progress-fill {
                height: 100%;
                border-radius: 5px;
                transition: width 0.3s ease;
            }
            .target-item .progress-fill.success { background: linear-gradient(90deg, #48bb78, #38a169); }
            .target-item .progress-fill.warning { background: linear-gradient(90deg, #ed8936, #dd6b20); }
            .target-item .progress-fill.danger { background: linear-gradient(90deg, #f56565, #e53e3e); }

            .activity-metrics {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .activity-box {
                background: #f7fafc;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
            }
            .activity-box .number {
                font-size: 28px;
                font-weight: 700;
                color: #1a1a2e;
            }
            .activity-box .label {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            .activity-box .sublabel {
                font-size: 11px;
                color: #888;
            }

            .stalled-list {
                max-height: 250px;
                overflow-y: auto;
            }
            .stalled-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }
            .stalled-item:last-child { border-bottom: none; }
            .stalled-item .info a {
                color: #1a1a2e;
                text-decoration: none;
                font-weight: 500;
            }
            .stalled-item .info a:hover { color: #4299e1; }
            .stalled-item .info .detail {
                font-size: 11px;
                color: #666;
            }
            .stalled-item .days {
                background: #fed7d7;
                color: #c53030;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }

            .quick-actions {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .quick-action {
                display: block;
                padding: 15px;
                background: #f7fafc;
                border-radius: 8px;
                text-align: center;
                text-decoration: none;
                color: #1a1a2e;
                font-weight: 500;
                font-size: 13px;
                transition: all 0.2s;
            }
            .quick-action:hover {
                background: #edf2f7;
                transform: translateY(-2px);
            }
        </style>

        <div class="bdm-dashboard">
            <div class="dashboard-header">
                <div>
                    <h1>My Dashboard</h1>
                    <div class="welcome">Welcome back, <?php echo htmlspecialchars($user->first_name); ?>!</div>
                </div>
                <form method="GET" class="filter-bar">
                    <input type="hidden" name="module" value="FunnelDashboard">
                    <input type="hidden" name="action" value="bdmdashboard">
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                    <button type="submit">Apply</button>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card revenue">
                    <div class="label">My Revenue</div>
                    <div class="value">$<?php echo number_format($data['revenue']['total_revenue']); ?></div>
                    <div class="subtext"><?php echo $data['revenue']['deal_count']; ?> deals closed</div>
                </div>
                <div class="summary-card deals">
                    <div class="label">My Leads</div>
                    <div class="value"><?php echo array_sum(array_column($data['my_leads'], 'count')); ?></div>
                    <div class="subtext">Active in pipeline</div>
                </div>
                <div class="summary-card commission">
                    <div class="label">Commission Earned</div>
                    <div class="value">$<?php echo number_format($data['revenue']['total_commission']); ?></div>
                    <div class="subtext">This period</div>
                </div>
                <div class="summary-card rank">
                    <div class="label">Leaderboard Rank</div>
                    <div class="value">#<?php echo $data['leaderboard_position'] ?: '-'; ?></div>
                    <div class="subtext">of <?php echo $data['leaderboard_total']; ?> BDMs</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- My Pipeline -->
                <div class="card">
                    <div class="card-header">
                        <span>My Pipeline</span>
                        <a href="index.php?module=Leads&action=index" style="font-size: 12px; color: #4299e1;">View All Leads</a>
                    </div>
                    <div class="card-body">
                        <div class="pipeline-stages">
                            <?php
                            $maxCount = max(array_column($data['my_leads'], 'count') ?: [1]);
                            foreach ($data['my_leads'] as $stage):
                                $width = ($stage['count'] / $maxCount) * 100;
                            ?>
                            <div class="pipeline-stage">
                                <div class="stage-name"><?php echo htmlspecialchars(str_replace('_', ' ', $stage['stage'])); ?></div>
                                <div class="stage-bar-container">
                                    <div class="stage-bar" style="width: <?php echo max($width, 5); ?>%;">
                                        <?php echo $stage['count']; ?>
                                    </div>
                                </div>
                                <div class="stage-count"><?php echo $stage['count']; ?></div>
                                <div class="stage-value">$<?php echo number_format($stage['value']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Targets & Activity -->
                <div class="card">
                    <div class="card-header">My Targets</div>
                    <div class="card-body">
                        <?php if (empty($data['my_targets'])): ?>
                            <p style="text-align: center; color: #666; padding: 20px;">No targets set for this period.</p>
                        <?php else: ?>
                            <div class="target-progress">
                                <?php foreach ($data['my_targets'] as $index => $target):
                                    $achievement = $data['achievements'][$index] ?? array();
                                    $revPct = $achievement['revenue'] ?? 0;
                                    $progressClass = $revPct >= 100 ? 'success' : ($revPct >= 70 ? 'warning' : 'danger');
                                ?>
                                <div class="target-item">
                                    <div class="header">
                                        <span class="name">Revenue Target</span>
                                        <span class="values">$<?php echo number_format($target['revenue_actual']); ?> / $<?php echo number_format($target['revenue_target']); ?></span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php echo $progressClass; ?>" style="width: <?php echo min($revPct, 100); ?>%;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="activity-metrics">
                            <div class="activity-box">
                                <div class="number"><?php echo $data['demos_metrics']['demos_scheduled']; ?></div>
                                <div class="label">Demos Scheduled</div>
                                <div class="sublabel"><?php echo $data['demos_metrics']['demos_completed']; ?> completed</div>
                            </div>
                            <div class="activity-box">
                                <div class="number"><?php echo $data['follow_up_metrics']['overdue']; ?></div>
                                <div class="label">Overdue Follow-ups</div>
                                <div class="sublabel"><?php echo $data['follow_up_metrics']['completion_rate']; ?>% on-time</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- My Stalled Leads -->
                <div class="card">
                    <div class="card-header">
                        <span>My Stalled Leads</span>
                        <span style="font-size: 12px; color: #c53030;"><?php echo count($data['my_stalled_leads']); ?> leads</span>
                    </div>
                    <div class="card-body">
                        <div class="stalled-list">
                            <?php if (empty($data['my_stalled_leads'])): ?>
                                <p style="text-align: center; color: #48bb78; padding: 20px;">Great job! No stalled leads.</p>
                            <?php else: ?>
                                <?php foreach ($data['my_stalled_leads'] as $lead): ?>
                                <div class="stalled-item">
                                    <div class="info">
                                        <a href="index.php?module=Leads&action=DetailView&record=<?php echo $lead['id']; ?>">
                                            <?php echo htmlspecialchars($lead['name']); ?>
                                        </a>
                                        <div class="detail">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $lead['pipeline_stage'] ?? 'New')); ?>
                                            | $<?php echo number_format($lead['expected_revenue']); ?>
                                        </div>
                                    </div>
                                    <span class="days"><?php echo $lead['days_stalled']; ?> days</span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">Quick Actions</div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="index.php?module=Leads&action=EditView" class="quick-action">+ New Lead</a>
                            <a href="index.php?module=Calls&action=EditView" class="quick-action">+ Log Call</a>
                            <a href="index.php?module=Meetings&action=EditView" class="quick-action">+ Schedule Demo</a>
                            <a href="index.php?module=Tasks&action=index" class="quick-action">View My Tasks</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
