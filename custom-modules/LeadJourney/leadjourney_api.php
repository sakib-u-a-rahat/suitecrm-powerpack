<?php
/**
 * LeadJourney API Endpoint
 * Returns timeline/recordings data as JSON for Angular frontend
 */

// Allow CORS for same-origin requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get parameters
$action = $_GET['api_action'] ?? 'timeline';
$parentType = $_GET['parent_type'] ?? 'Leads';
$parentId = $_GET['parent_id'] ?? '';

if (empty($parentId) || !preg_match('/^[a-f0-9-]{36}$/i', $parentId)) {
    echo json_encode(['error' => 'Invalid parent_id', 'data' => []]);
    exit;
}

// Validate parent_type
if (!in_array($parentType, ['Leads', 'Contacts'])) {
    echo json_encode(['error' => 'Invalid parent_type', 'data' => []]);
    exit;
}

// Database connection from config
$configFile = dirname(__FILE__) . '/../../config.php';
if (!file_exists($configFile)) {
    $configFile = '/bitnami/suitecrm/public/legacy/config.php';
}

if (!file_exists($configFile)) {
    echo json_encode(['error' => 'Config not found', 'data' => []]);
    exit;
}

$sugar_config = [];
require_once($configFile);

$dbHost = $sugar_config['dbconfig']['db_host_name'] ?? 'localhost';
$dbUser = $sugar_config['dbconfig']['db_user_name'] ?? 'root';
$dbPass = $sugar_config['dbconfig']['db_password'] ?? '';
$dbName = $sugar_config['dbconfig']['db_name'] ?? 'suitecrm';
$dbPort = $sugar_config['dbconfig']['db_port'] ?? 3306;

// Connect to database with SSL support for managed databases
$mysqli = mysqli_init();
if (!$mysqli) {
    echo json_encode(['error' => 'mysqli_init failed', 'data' => []]);
    exit;
}

// Check if SSL CA cert exists (for managed databases like DigitalOcean)
$sslCaFile = '/opt/bitnami/mysql/certs/ca-certificate.crt';
if (file_exists($sslCaFile)) {
    $mysqli->ssl_set(null, null, $sslCaFile, null, null);
    $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
}

if (!$mysqli->real_connect($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort)) {
    echo json_encode(['error' => 'Database connection failed: ' . $mysqli->connect_error, 'data' => []]);
    exit;
}

if ($action === 'timeline') {
    $entries = [];

    // 1. Get LeadJourney custom touchpoints (calls, verbacall, etc.)
    $stmt1 = $mysqli->prepare("
        SELECT
            id, name, touchpoint_type, touchpoint_date, touchpoint_data,
            source, description, recording_url, thread_id, date_entered
        FROM lead_journey
        WHERE parent_type = ? AND parent_id = ? AND deleted = 0
        ORDER BY touchpoint_date DESC, date_entered DESC
        LIMIT 100
    ");
    if ($stmt1) {
        $stmt1->bind_param('ss', $parentType, $parentId);
        $stmt1->execute();
        $result = $stmt1->get_result();

        while ($row = $result->fetch_assoc()) {
            if (!empty($row['touchpoint_data'])) {
                $row['touchpoint_data'] = json_decode($row['touchpoint_data'], true);
            }
            $entries[] = $row;
        }
        $stmt1->close();
    }

    // 2. Get Notes (includes SMS stored as Notes)
    $stmt2 = $mysqli->prepare("
        SELECT id, name, description, date_entered, date_modified
        FROM notes
        WHERE parent_type = ? AND parent_id = ? AND deleted = 0
        ORDER BY date_entered DESC
        LIMIT 100
    ");
    if ($stmt2) {
        $stmt2->bind_param('ss', $parentType, $parentId);
        $stmt2->execute();
        $result = $stmt2->get_result();

        while ($row = $result->fetch_assoc()) {
            $name = $row['name'] ?? '';
            $description = $row['description'] ?? '';

            // Determine if this is an SMS based on the name pattern
            $isSms = (stripos($name, 'SMS') !== false);
            $isInbound = (stripos($name, 'from') !== false);

            if ($isSms) {
                $touchpointType = $isInbound ? 'sms_inbound' : 'sms_outbound';
            } else {
                $touchpointType = 'note';
            }

            $entries[] = [
                'id' => $row['id'],
                'name' => $name,
                'touchpoint_type' => $touchpointType,
                'touchpoint_date' => $row['date_entered'],
                'touchpoint_data' => null,
                'source' => 'notes',
                'description' => $description,
                'recording_url' => null,
                'thread_id' => null,
                'date_entered' => $row['date_entered']
            ];
        }
        $stmt2->close();
    }

    // 3. Get Calls from SuiteCRM Calls module
    $stmt3 = $mysqli->prepare("
        SELECT c.id, c.name, c.date_start, c.duration_hours, c.duration_minutes,
               c.status, c.direction, c.description
        FROM calls c
        WHERE c.parent_type = ? AND c.parent_id = ? AND c.deleted = 0
        ORDER BY c.date_start DESC
        LIMIT 100
    ");
    if ($stmt3) {
        $stmt3->bind_param('ss', $parentType, $parentId);
        $stmt3->execute();
        $result = $stmt3->get_result();

        while ($row = $result->fetch_assoc()) {
            $direction = strtolower($row['direction'] ?? 'outbound');
            $touchpointType = ($direction === 'inbound') ? 'call_inbound' : 'call_outbound';
            $durationMins = (intval($row['duration_hours'] ?? 0) * 60) + intval($row['duration_minutes'] ?? 0);

            $entries[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'touchpoint_type' => $touchpointType,
                'touchpoint_date' => $row['date_start'],
                'touchpoint_data' => [
                    'direction' => $direction,
                    'status' => $row['status'],
                    'duration' => $durationMins
                ],
                'source' => 'calls',
                'description' => $row['description'],
                'recording_url' => null,
                'thread_id' => null,
                'date_entered' => $row['date_start']
            ];
        }
        $stmt3->close();
    }

    // 4. Get Emails (email body is in emails_text table)
    $stmt4 = $mysqli->prepare("
        SELECT e.id, e.name, e.date_sent_received, e.status, e.type,
               SUBSTRING(COALESCE(et.description_html, et.description, ''), 1, 500) as description_preview
        FROM emails e
        JOIN emails_beans eb ON e.id = eb.email_id
        LEFT JOIN emails_text et ON e.id = et.email_id
        WHERE eb.bean_module = ? AND eb.bean_id = ? AND e.deleted = 0
        ORDER BY e.date_sent_received DESC
        LIMIT 100
    ");
    if ($stmt4) {
        $stmt4->bind_param('ss', $parentType, $parentId);
        $stmt4->execute();
        $result = $stmt4->get_result();

        while ($row = $result->fetch_assoc()) {
            $emailType = strtolower($row['type'] ?? 'out');
            $touchpointType = ($emailType === 'inbound') ? 'email_inbound' : 'email_outbound';

            $entries[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'touchpoint_type' => $touchpointType,
                'touchpoint_date' => $row['date_sent_received'],
                'touchpoint_data' => [
                    'status' => $row['status'],
                    'type' => $row['type']
                ],
                'source' => 'emails',
                'description' => strip_tags($row['description_preview'] ?? ''),
                'recording_url' => null,
                'thread_id' => null,
                'date_entered' => $row['date_sent_received']
            ];
        }
        $stmt4->close();
    }

    // Sort all entries by date descending
    usort($entries, function($a, $b) {
        $dateA = strtotime($a['touchpoint_date'] ?? $a['date_entered'] ?? '0');
        $dateB = strtotime($b['touchpoint_date'] ?? $b['date_entered'] ?? '0');
        return $dateB - $dateA;
    });

    // Limit to 200 total entries
    $entries = array_slice($entries, 0, 200);

    echo json_encode([
        'success' => true,
        'parent_type' => $parentType,
        'parent_id' => $parentId,
        'count' => count($entries),
        'data' => $entries
    ]);

} elseif ($action === 'recordings') {
    // Get call recordings from lead_journey (calls with recording_url)
    $stmt = $mysqli->prepare("
        SELECT
            id, name, touchpoint_type, touchpoint_date, touchpoint_data,
            recording_url, date_entered
        FROM lead_journey
        WHERE parent_type = ? AND parent_id = ? AND deleted = 0
          AND recording_url IS NOT NULL AND recording_url != ''
        ORDER BY touchpoint_date DESC, date_entered DESC
        LIMIT 50
    ");
    $stmt->bind_param('ss', $parentType, $parentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $recordings = [];
    while ($row = $result->fetch_assoc()) {
        // Decode touchpoint_data JSON if present
        if (!empty($row['touchpoint_data'])) {
            $data = json_decode($row['touchpoint_data'], true);
            $row['from_number'] = $data['from'] ?? $data['from_number'] ?? 'Unknown';
            $row['to_number'] = $data['to'] ?? $data['to_number'] ?? 'Unknown';
            $row['duration'] = $data['duration'] ?? null;
            $row['direction'] = $data['direction'] ?? (strpos($row['touchpoint_type'], 'inbound') !== false ? 'inbound' : 'outbound');
        }
        $recordings[] = $row;
    }

    echo json_encode([
        'success' => true,
        'parent_type' => $parentType,
        'parent_id' => $parentId,
        'count' => count($recordings),
        'data' => $recordings
    ]);

} else {
    echo json_encode(['error' => 'Unknown action', 'data' => []]);
}

$mysqli->close();
