<?php
/**
 * Backfill Calls Script
 * Creates SuiteCRM Call records from existing lead_journey entries
 * Run via: php backfill_calls.php
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from CLI");
}

// Bootstrap SuiteCRM
chdir(dirname(__FILE__) . '/../../');
define('sugarEntry', true);
require_once('include/entryPoint.php');

global $db;

echo "=== Backfill Calls from LeadJourney ===\n\n";

// Get all call entries from lead_journey that have call_sid and don't have a corresponding Call record
$sql = "SELECT lj.id, lj.name, lj.parent_type, lj.parent_id, lj.touchpoint_type,
               lj.touchpoint_date, lj.touchpoint_data, lj.recording_url
        FROM lead_journey lj
        WHERE lj.deleted = 0
        AND lj.touchpoint_type IN ('call_inbound', 'call_outbound')
        AND lj.touchpoint_data LIKE '%\"call_sid\"%'
        ORDER BY lj.touchpoint_date DESC
        LIMIT 500";

$result = $db->query($sql);

$created = 0;
$skipped = 0;

$count = 0;
while ($row = $db->fetchByAssoc($result)) {
    $count++;
    $journeyId = $row['id'];
    $parentType = $row['parent_type'];
    $parentId = $row['parent_id'];
    $rawData = $row['touchpoint_data'];
    // Decode HTML entities (data may be stored with &quot; etc.)
    $rawData = html_entity_decode($rawData, ENT_QUOTES, 'UTF-8');
    $touchpointData = json_decode($rawData, true) ?? [];
    $callSid = $touchpointData['call_sid'] ?? '';

    // Debug first 3
    if ($count <= 3) {
        $jsonError = json_last_error_msg();
        echo "DEBUG {$journeyId}: rawData length=" . strlen($rawData ?? '') . ", jsonError=" . $jsonError . "\n";
        echo "  First 100 chars: " . substr($rawData ?? '', 0, 100) . "\n";
    }

    if (empty($callSid)) {
        echo "Skipping {$journeyId}: No call_sid\n";
        $skipped++;
        continue;
    }

    // Check if Call record already exists
    $existingCallSql = "SELECT id FROM calls
                        WHERE parent_type = " . $db->quoted($parentType) . "
                        AND parent_id = " . $db->quoted($parentId) . "
                        AND description LIKE " . $db->quoted('%' . $callSid . '%') . "
                        AND deleted = 0 LIMIT 1";
    $existingCall = $db->getOne($existingCallSql);

    if ($existingCall) {
        echo "Skipping {$journeyId}: Call record already exists\n";
        $skipped++;
        continue;
    }

    // Get lead name
    $leadNameSql = "SELECT first_name, last_name FROM leads WHERE id = " . $db->quoted($parentId);
    $leadResult = $db->query($leadNameSql);
    $leadRow = $db->fetchByAssoc($leadResult);
    $leadName = trim(($leadRow['first_name'] ?? '') . ' ' . ($leadRow['last_name'] ?? ''));

    if (empty($leadName)) {
        $leadName = 'Unknown';
    }

    $direction = $touchpointData['direction'] ?? 'outbound';
    $status = $touchpointData['status'] ?? 'completed';
    $duration = intval($touchpointData['duration'] ?? 0);
    $from = $touchpointData['from'] ?? '';
    $to = $touchpointData['to'] ?? '';

    // Create Call record
    $callBean = BeanFactory::newBean('Calls');
    $callBean->name = ($direction === 'inbound' ? 'Call from ' : 'Call to ') . $leadName;
    $callBean->direction = ucfirst($direction);
    $callBean->status = ($status === 'completed') ? 'Held' : 'Not Held';
    $callBean->date_start = $row['touchpoint_date'];
    $callBean->date_end = date('Y-m-d H:i:s', strtotime($row['touchpoint_date']) + $duration);
    $callBean->duration_hours = floor($duration / 3600);
    $callBean->duration_minutes = floor(($duration % 3600) / 60);
    $callBean->parent_type = $parentType;
    $callBean->parent_id = $parentId;
    $callBean->description = "Twilio Call SID: {$callSid}\n" .
        "From: {$from}\n" .
        "To: {$to}\n" .
        "Duration: " . floor($duration / 60) . "m " . ($duration % 60) . "s\n" .
        ($row['recording_url'] ? "Recording: {$row['recording_url']}" : "");
    $callBean->save();

    echo "Created call for {$leadName}: {$callBean->id}\n";
    $created++;
}

echo "\n=== Backfill Complete ===\n";
echo "Created: $created\n";
echo "Skipped: $skipped\n";
