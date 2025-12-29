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

// Connect to database
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
if ($mysqli->connect_error) {
    echo json_encode(['error' => 'Database connection failed', 'data' => []]);
    exit;
}

// Set SSL if needed
$mysqli->ssl_set(null, null, null, null, null);

if ($action === 'timeline') {
    // Get timeline entries (include thread_id for conversation grouping)
    $stmt = $mysqli->prepare("
        SELECT
            id, name, touchpoint_type, touchpoint_date, touchpoint_data,
            source, description, recording_url, thread_id, date_entered
        FROM lead_journey
        WHERE parent_type = ? AND parent_id = ? AND deleted = 0
        ORDER BY touchpoint_date DESC, date_entered DESC
        LIMIT 100
    ");
    $stmt->bind_param('ss', $parentType, $parentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $entries = [];
    while ($row = $result->fetch_assoc()) {
        // Decode touchpoint_data JSON if present
        if (!empty($row['touchpoint_data'])) {
            $row['touchpoint_data'] = json_decode($row['touchpoint_data'], true);
        }
        $entries[] = $row;
    }

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
