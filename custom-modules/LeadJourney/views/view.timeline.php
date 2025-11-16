<?php
require_once('include/MVC/View/SugarView.php');

class LeadJourneyViewTimeline extends SugarView {
    public function display() {
        $parentType = $_GET['parent_type'] ?? 'Leads';
        $parentId = $_GET['parent_id'] ?? '';
        
        if (empty($parentId)) {
            echo '<div class="alert alert-warning">No record specified</div>';
            return;
        }
        
        // Get the parent record
        $parent = BeanFactory::getBean($parentType, $parentId);
        
        if (!$parent) {
            echo '<div class="alert alert-danger">Record not found</div>';
            return;
        }
        
        // Get timeline data
        $timeline = LeadJourney::getJourneyTimeline($parentType, $parentId);
        
        echo $this->renderTimeline($parent, $timeline);
    }
    
    private function renderTimeline($parent, $timeline) {
        ob_start();
        ?>
        <style>
            .journey-timeline {
                max-width: 1200px;
                margin: 20px auto;
                padding: 20px;
                background: #fff;
            }
            .timeline-header {
                border-bottom: 2px solid #0070d2;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .timeline-header h2 {
                margin: 0 0 10px 0;
                color: #333;
            }
            .timeline-stats {
                display: flex;
                gap: 30px;
                margin-top: 15px;
            }
            .stat-box {
                padding: 10px 20px;
                background: #f4f6f9;
                border-radius: 4px;
            }
            .stat-box .label {
                font-size: 12px;
                color: #666;
                text-transform: uppercase;
            }
            .stat-box .value {
                font-size: 24px;
                font-weight: bold;
                color: #0070d2;
            }
            .timeline-list {
                position: relative;
                padding-left: 40px;
            }
            .timeline-list::before {
                content: '';
                position: absolute;
                left: 15px;
                top: 0;
                bottom: 0;
                width: 2px;
                background: #e0e0e0;
            }
            .timeline-item {
                position: relative;
                margin-bottom: 30px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 8px;
                border-left: 4px solid #0070d2;
            }
            .timeline-item::before {
                content: '';
                position: absolute;
                left: -29px;
                top: 20px;
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #0070d2;
                border: 3px solid #fff;
            }
            .timeline-item.call::before { background: #34a853; }
            .timeline-item.email::before { background: #fbbc04; }
            .timeline-item.meeting::before { background: #ea4335; }
            .timeline-item.site_visit::before { background: #4285f4; }
            .timeline-item.linkedin_click::before { background: #0077b5; }
            .timeline-item.campaign::before { background: #9c27b0; }
            
            .timeline-icon {
                display: inline-block;
                width: 30px;
                height: 30px;
                margin-right: 10px;
                text-align: center;
                line-height: 30px;
                border-radius: 50%;
                background: #0070d2;
                color: white;
            }
            .timeline-title {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .timeline-date {
                font-size: 12px;
                color: #666;
                margin-bottom: 10px;
            }
            .timeline-description {
                color: #555;
                line-height: 1.5;
            }
            .timeline-meta {
                margin-top: 10px;
                font-size: 12px;
                color: #888;
            }
            .filter-buttons {
                margin-bottom: 20px;
            }
            .filter-btn {
                padding: 8px 16px;
                margin-right: 10px;
                border: 1px solid #ddd;
                background: white;
                cursor: pointer;
                border-radius: 4px;
            }
            .filter-btn.active {
                background: #0070d2;
                color: white;
                border-color: #0070d2;
            }
        </style>
        
        <div class="journey-timeline">
            <div class="timeline-header">
                <h2>Journey Timeline: <?php echo htmlspecialchars($parent->name); ?></h2>
                <div class="timeline-stats">
                    <div class="stat-box">
                        <div class="label">Total Touchpoints</div>
                        <div class="value"><?php echo count($timeline); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="label">Calls</div>
                        <div class="value"><?php echo $this->countByType($timeline, 'call'); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="label">Emails</div>
                        <div class="value"><?php echo $this->countByType($timeline, 'email'); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="label">Meetings</div>
                        <div class="value"><?php echo $this->countByType($timeline, 'meeting'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="call">Calls</button>
                <button class="filter-btn" data-filter="email">Emails</button>
                <button class="filter-btn" data-filter="meeting">Meetings</button>
                <button class="filter-btn" data-filter="site_visit">Site Visits</button>
                <button class="filter-btn" data-filter="linkedin_click">LinkedIn</button>
                <button class="filter-btn" data-filter="campaign">Campaigns</button>
            </div>
            
            <div class="timeline-list">
                <?php foreach ($timeline as $item): ?>
                    <div class="timeline-item <?php echo htmlspecialchars($item['type']); ?>" data-type="<?php echo htmlspecialchars($item['type']); ?>">
                        <div class="timeline-icon">
                            <?php echo $this->getIcon($item['icon']); ?>
                        </div>
                        <div class="timeline-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="timeline-date"><?php echo date('F j, Y g:i A', strtotime($item['date'])); ?></div>
                        <div class="timeline-description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                        <div class="timeline-meta">
                            <?php if (isset($item['duration'])): ?>
                                Duration: <?php echo $item['duration']; ?> minutes
                            <?php endif; ?>
                            <?php if (isset($item['status'])): ?>
                                | Status: <?php echo htmlspecialchars($item['status']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
            // Filter functionality
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    
                    // Update active button
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter timeline items
                    document.querySelectorAll('.timeline-item').forEach(item => {
                        if (filter === 'all' || item.dataset.type === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function countByType($timeline, $type) {
        return count(array_filter($timeline, function($item) use ($type) {
            return $item['type'] === $type;
        }));
    }
    
    private function getIcon($icon) {
        $icons = array(
            'phone' => '📞',
            'envelope' => '✉️',
            'calendar' => '📅',
            'globe' => '🌐',
            'linkedin' => '💼',
            'bullhorn' => '📢',
        );
        return $icons[$icon] ?? '•';
    }
}
