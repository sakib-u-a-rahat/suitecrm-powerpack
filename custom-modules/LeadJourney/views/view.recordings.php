<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/MVC/View/SugarView.php');

/**
 * Recordings View - Shows call recordings filtered by Lead/Contact phone numbers
 */
class LeadJourneyViewRecordings extends SugarView
{
    public function display()
    {
        global $db, $current_user;

        $parentType = $_GET['parent_type'] ?? $_REQUEST['parent_type'] ?? '';
        $parentId = $_GET['parent_id'] ?? $_REQUEST['parent_id'] ?? '';

        if (empty($parentType) || empty($parentId)) {
            echo $this->renderError('Missing parent_type or parent_id parameter');
            return;
        }

        // Check permission
        if (!$this->hasRecordingPermission()) {
            echo $this->renderError('You do not have permission to view recordings');
            return;
        }

        // Get parent record
        $parent = BeanFactory::getBean($parentType, $parentId);
        if (!$parent || empty($parent->id)) {
            echo $this->renderError('Record not found');
            return;
        }

        // Get phone numbers
        $phoneNumbers = $this->getPhoneNumbers($parent);

        // Get recordings
        $recordings = $this->getRecordings($parentType, $parentId, $phoneNumbers);

        // Render
        echo $this->renderRecordingsPage($parent, $recordings, $parentType, $parentId);
    }

    private function hasRecordingPermission()
    {
        global $current_user;

        if ($current_user->isAdmin()) {
            return true;
        }

        require_once('modules/ACLActions/ACLAction.php');
        $actions = ACLAction::getUserActions($current_user->id, false, 'TwilioIntegration');

        if (isset($actions['TwilioIntegration']['module']['view_recordings']['aclaccess'])) {
            return $actions['TwilioIntegration']['module']['view_recordings']['aclaccess'] >= ACL_ALLOW_ENABLED;
        }

        // Default allow if ACL not configured
        return true;
    }

    private function getPhoneNumbers($bean)
    {
        $phones = array();
        $phoneFields = array('phone_mobile', 'phone_work', 'phone_home', 'phone_other', 'phone_fax');

        foreach ($phoneFields as $field) {
            if (!empty($bean->$field)) {
                $phones[] = $bean->$field;
            }
        }

        return $phones;
    }

    private function normalizePhone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    private function getRecordings($parentType, $parentId, $phoneNumbers)
    {
        global $db;

        $recordings = array();

        // Build phone search patterns for JSON data
        $phonePatterns = array();
        foreach ($phoneNumbers as $phone) {
            $normalized = $this->normalizePhone($phone);
            if (strlen($normalized) >= 10) {
                $last10 = substr($normalized, -10);
                $phonePatterns[] = $last10;
            }
        }

        // Query twilio_audit_log for recordings
        // The data is stored as JSON in the 'data' column
        $sql = "SELECT id, action, data, date_created
                FROM twilio_audit_log
                WHERE (action LIKE '%recording%' OR action LIKE '%call%')
                ORDER BY date_created DESC
                LIMIT 200";

        $result = $db->query($sql);

        while ($row = $db->fetchByAssoc($result)) {
            $data = json_decode($row['data'], true);
            if (!$data) continue;

            // Check if this entry has a recording URL
            $recordingUrl = $data['recording_url'] ?? $data['RecordingUrl'] ?? '';
            if (empty($recordingUrl)) continue;

            // Extract phone numbers from data
            $fromNumber = $data['from_number'] ?? $data['From'] ?? $data['Caller'] ?? '';
            $toNumber = $data['to_number'] ?? $data['To'] ?? $data['Called'] ?? '';

            // Check if this call matches the lead's phone numbers
            $matches = false;
            if (!empty($phonePatterns)) {
                foreach ($phonePatterns as $pattern) {
                    if (strpos($this->normalizePhone($fromNumber), $pattern) !== false ||
                        strpos($this->normalizePhone($toNumber), $pattern) !== false) {
                        $matches = true;
                        break;
                    }
                }
            }

            // Also match by parent_id in the data
            $dataParentId = $data['parent_id'] ?? $data['lead_id'] ?? $data['contact_id'] ?? '';
            if ($dataParentId === $parentId) {
                $matches = true;
            }

            if ($matches) {
                $recordings[] = array(
                    'id' => $row['id'],
                    'recording_url' => $recordingUrl,
                    'from_number' => $fromNumber,
                    'to_number' => $toNumber,
                    'direction' => $data['direction'] ?? $data['Direction'] ?? 'outbound',
                    'duration' => $data['duration'] ?? $data['RecordingDuration'] ?? $data['CallDuration'] ?? 0,
                    'date_entered' => $row['date_created'],
                    'call_sid' => $data['call_sid'] ?? $data['CallSid'] ?? '',
                );
            }
        }

        return $recordings;
    }

    private function renderRecordingsPage($parent, $recordings, $parentType, $parentId)
    {
        $parentName = $parent->name ?? ($parent->first_name . ' ' . $parent->last_name);
        $recordCount = count($recordings);

        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Call Recordings - ' . htmlspecialchars($parentName) . '</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header h1 { font-size: 24px; color: #333; margin-bottom: 5px; }
        .header .subtitle { color: #666; font-size: 14px; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .recordings-grid { display: grid; gap: 15px; }
        .recording-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .recording-card .meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .recording-card .date { font-size: 14px; color: #666; }
        .recording-card .duration { background: #e3f2fd; color: #1976d2; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .recording-card .phones { margin-bottom: 15px; }
        .recording-card .phone-row { display: flex; align-items: center; gap: 10px; margin: 5px 0; font-size: 14px; }
        .recording-card .phone-label { color: #666; min-width: 50px; }
        .recording-card .phone-number { font-weight: 500; }
        .recording-card .direction { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; }
        .recording-card .direction.inbound { background: #e8f5e9; color: #2e7d32; }
        .recording-card .direction.outbound { background: #fff3e0; color: #ef6c00; }
        .recording-card audio { width: 100%; margin-top: 10px; }
        .recording-card .actions { margin-top: 15px; display: flex; gap: 10px; }
        .recording-card .btn { padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 500; }
        .recording-card .btn-primary { background: #007bff; color: #fff; }
        .recording-card .btn-secondary { background: #f5f5f5; color: #333; border: 1px solid #ddd; }
        .empty-state { background: #fff; padding: 60px 20px; text-align: center; border-radius: 8px; }
        .empty-state h3 { color: #666; margin-bottom: 10px; }
        .empty-state p { color: #999; }
        .filter-bar { background: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; }
        .filter-bar label { font-size: 14px; color: #666; }
        .filter-bar select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php?module=' . $parentType . '&action=DetailView&record=' . $parentId . '" class="back-link">← Back to ' . htmlspecialchars($parentName) . '</a>

        <div class="header">
            <h1>Call Recordings</h1>
            <div class="subtitle">' . $recordCount . ' recording(s) for ' . htmlspecialchars($parentName) . '</div>
        </div>';

        if (empty($recordings)) {
            $html .= '
        <div class="empty-state">
            <h3>No Recordings Found</h3>
            <p>There are no call recordings associated with this record.</p>
        </div>';
        } else {
            $html .= '
        <div class="filter-bar">
            <label>Filter:</label>
            <select id="directionFilter" onchange="filterRecordings()">
                <option value="all">All Directions</option>
                <option value="inbound">Inbound Only</option>
                <option value="outbound">Outbound Only</option>
            </select>
        </div>

        <div class="recordings-grid">';

            foreach ($recordings as $rec) {
                $date = date('M j, Y g:i A', strtotime($rec['date_entered']));
                $duration = $this->formatDuration($rec['duration'] ?? 0);
                $direction = strtolower($rec['direction'] ?? 'outbound');
                $directionLabel = ucfirst($direction);

                $html .= '
            <div class="recording-card" data-direction="' . $direction . '">
                <div class="meta">
                    <span class="date">' . $date . '</span>
                    <span class="duration">' . $duration . '</span>
                </div>
                <div class="phones">
                    <div class="phone-row">
                        <span class="phone-label">From:</span>
                        <span class="phone-number">' . htmlspecialchars($rec['from_number']) . '</span>
                        <span class="direction ' . $direction . '">' . $directionLabel . '</span>
                    </div>
                    <div class="phone-row">
                        <span class="phone-label">To:</span>
                        <span class="phone-number">' . htmlspecialchars($rec['to_number']) . '</span>
                    </div>
                </div>
                <audio controls preload="none">
                    <source src="' . htmlspecialchars($rec['recording_url']) . '" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                <div class="actions">
                    <a href="' . htmlspecialchars($rec['recording_url']) . '" target="_blank" class="btn btn-primary">Download</a>
                </div>
            </div>';
            }

            $html .= '
        </div>

        <script>
            function filterRecordings() {
                var filter = document.getElementById("directionFilter").value;
                var cards = document.querySelectorAll(".recording-card");
                cards.forEach(function(card) {
                    if (filter === "all" || card.dataset.direction === filter) {
                        card.style.display = "block";
                    } else {
                        card.style.display = "none";
                    }
                });
            }
        </script>';
        }

        $html .= '
    </div>
</body>
</html>';

        return $html;
    }

    private function formatDuration($seconds)
    {
        $seconds = intval($seconds);
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $minutes . 'm ' . $secs . 's';
    }

    private function renderError($message)
    {
        return '<!DOCTYPE html>
<html>
<head><title>Error</title>
<style>body{font-family:sans-serif;padding:40px;text-align:center;}.error{color:#c62828;}</style>
</head>
<body>
<h2 class="error">Error</h2>
<p>' . htmlspecialchars($message) . '</p>
<a href="javascript:history.back()">Go Back</a>
</body>
</html>';
    }
}
