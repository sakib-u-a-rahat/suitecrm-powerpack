<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/**
 * Get Call Recordings for a Lead/Contact
 * Filters recordings by the lead's phone number (caller or recipient)
 */
function getCallRecordingsSubpanelData($params)
{
    global $db, $current_user;

    $parentBean = $params['parent_bean'] ?? null;
    if (!$parentBean || empty($parentBean->id)) {
        return array();
    }

    // Check if user has permission to view recordings
    if (!hasRecordingPermission()) {
        return array();
    }

    $parentType = $parentBean->module_dir;
    $parentId = $parentBean->id;

    // Get phone numbers for this lead/contact
    $phoneNumbers = getRecordPhoneNumbers($parentBean);

    if (empty($phoneNumbers)) {
        return array();
    }

    // Build query to find recordings linked to calls with these phone numbers
    $phoneList = implode("','", array_map(function($p) use ($db) {
        return $db->quote(normalizePhone($p));
    }, $phoneNumbers));

    // Query Documents that are call recordings for this lead
    // Option 1: Documents linked directly to this Lead/Contact
    $sql = "SELECT DISTINCT d.id
            FROM documents d
            JOIN documents_leads dl ON d.id = dl.document_id AND dl.deleted = 0
            WHERE dl.lead_id = " . $db->quoted($parentId) . "
            AND d.deleted = 0
            AND (d.category_id = 'CallRecording' OR d.document_name LIKE '%recording%')
            ORDER BY d.date_entered DESC
            LIMIT 50";

    if ($parentType === 'Contacts') {
        $sql = "SELECT DISTINCT d.id
                FROM documents d
                JOIN documents_contacts dc ON d.id = dc.document_id AND dc.deleted = 0
                WHERE dc.contact_id = " . $db->quoted($parentId) . "
                AND d.deleted = 0
                AND (d.category_id = 'CallRecording' OR d.document_name LIKE '%recording%')
                ORDER BY d.date_entered DESC
                LIMIT 50";
    }

    $result = $db->query($sql);
    $documents = array();

    while ($row = $db->fetchByAssoc($result)) {
        $doc = BeanFactory::getBean('Documents', $row['id']);
        if ($doc && !empty($doc->id)) {
            $documents[] = $doc;
        }
    }

    // Also check twilio_audit_log for recordings linked to this phone
    if (empty($documents) && !empty($phoneNumbers)) {
        $recordings = getRecordingsFromAuditLog($parentType, $parentId, $phoneNumbers);
        $documents = array_merge($documents, $recordings);
    }

    return $documents;
}

/**
 * Check if current user has permission to view recordings
 */
function hasRecordingPermission()
{
    global $current_user;

    if ($current_user->isAdmin()) {
        return true;
    }

    // Check ACL for view_recordings action
    require_once('modules/ACLActions/ACLAction.php');
    $actions = ACLAction::getUserActions($current_user->id, false, 'TwilioIntegration');

    if (isset($actions['TwilioIntegration']['module']['view_recordings']['aclaccess'])) {
        return $actions['TwilioIntegration']['module']['view_recordings']['aclaccess'] >= ACL_ALLOW_ENABLED;
    }

    return false;
}

/**
 * Get phone numbers from a Lead/Contact record
 */
function getRecordPhoneNumbers($bean)
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

/**
 * Normalize phone number for comparison
 */
function normalizePhone($phone)
{
    return preg_replace('/[^0-9]/', '', $phone);
}

/**
 * Get recordings from twilio_audit_log linked to these phone numbers
 * The audit log stores data as JSON in the 'data' column
 */
function getRecordingsFromAuditLog($parentType, $parentId, $phoneNumbers)
{
    global $db;

    $documents = array();
    $phonePatterns = array();

    foreach ($phoneNumbers as $phone) {
        $normalized = normalizePhone($phone);
        if (strlen($normalized) >= 10) {
            $phonePatterns[] = substr($normalized, -10);
        }
    }

    // Query twilio_audit_log for recordings (data stored as JSON)
    $sql = "SELECT id, action, data, date_created
            FROM twilio_audit_log
            WHERE (action LIKE '%recording%' OR action LIKE '%call%')
            ORDER BY date_created DESC
            LIMIT 100";

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
                if (strpos(normalizePhone($fromNumber), $pattern) !== false ||
                    strpos(normalizePhone($toNumber), $pattern) !== false) {
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
            // Create a pseudo-document bean for display
            $doc = new stdClass();
            $doc->id = $data['call_sid'] ?? $data['CallSid'] ?? $row['id'];
            $doc->document_name = 'Call Recording - ' . date('M j, Y g:i A', strtotime($row['date_created']));
            $doc->description = "From: {$fromNumber} → To: {$toNumber}";
            $doc->date_entered = $row['date_created'];
            $doc->recording_url = $recordingUrl;
            $doc->category_id = 'CallRecording';
            $documents[] = $doc;
        }
    }

    return $documents;
}
