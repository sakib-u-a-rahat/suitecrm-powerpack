<?php
/**
 * TwiML Response Generator
 * Handles voice webhook responses for Twilio
 * Supports: outbound calls, inbound routing, voicemail, call status
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/TwilioIntegration/TwilioIntegration.php');

class TwilioIntegrationViewTwiml extends SugarView
{
    public function display()
    {
        // Always return TwiML
        header('Content-Type: application/xml');
        
        // Get parameters
        $action = isset($_REQUEST['dial_action']) ? $_REQUEST['dial_action'] : 'outbound';
        $to = isset($_REQUEST['To']) ? $_REQUEST['To'] : '';
        $from = isset($_REQUEST['From']) ? $_REQUEST['From'] : '';
        $callStatus = isset($_REQUEST['CallStatus']) ? $_REQUEST['CallStatus'] : '';
        $callSid = isset($_REQUEST['CallSid']) ? $_REQUEST['CallSid'] : '';
        
        // Log for debugging
        $GLOBALS['log']->info("TwiML Request - Action: $action, To: $to, From: $from, Status: $callStatus");
        
        switch ($action) {
            case 'outbound':
                $this->handleOutboundCall($to);
                break;
            case 'inbound':
                $this->handleInboundCall($from, $to);
                break;
            case 'voicemail':
                $this->handleVoicemail($from);
                break;
            case 'dial_status':
                $this->handleDialStatus($callSid, $callStatus);
                break;
            case 'recording':
                $this->handleRecording();
                break;
            default:
                $this->handleOutboundCall($to);
        }
        
        die();
    }
    
    /**
     * Handle outbound call - connect caller to destination
     */
    private function handleOutboundCall($to)
    {
        $config = TwilioIntegration::getConfig();
        $siteUrl = $GLOBALS['sugar_config']['site_url'] ?? '';
        
        // Clean phone number
        $to = $this->cleanPhoneNumber($to);
        
        $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
        $twiml .= '<Response>';
        
        if (!empty($to)) {
            // Status callback for when the dialed leg completes
            $statusCallback = $siteUrl . '/index.php?module=TwilioIntegration&action=twiml&dial_action=dial_status';
            
            $twiml .= '<Say voice="Polly.Joanna">Connecting your call. Please wait.</Say>';
            $twiml .= '<Dial callerId="' . htmlspecialchars($config['phone_number']) . '" ';
            $twiml .= 'timeout="30" ';
            $twiml .= 'action="' . htmlspecialchars($statusCallback) . '" ';
            $twiml .= 'method="POST">';
            $twiml .= '<Number>' . htmlspecialchars($to) . '</Number>';
            $twiml .= '</Dial>';
        } else {
            $twiml .= '<Say voice="Polly.Joanna">Sorry, no destination number was provided.</Say>';
            $twiml .= '<Hangup/>';
        }
        
        $twiml .= '</Response>';
        
        echo $twiml;
    }
    
    /**
     * Handle inbound call - route to BDM or voicemail
     */
    private function handleInboundCall($from, $to)
    {
        $config = TwilioIntegration::getConfig();
        $siteUrl = $GLOBALS['sugar_config']['site_url'] ?? '';
        
        // Try to find a lead/contact for this caller
        $leadInfo = $this->findLeadByPhone($from);
        
        // Get BDM phone number (assigned user's phone)
        $bdmPhone = $this->getBDMPhone($leadInfo);
        
        $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
        $twiml .= '<Response>';
        
        // Personalize greeting if we know the caller
        if ($leadInfo && !empty($leadInfo['name'])) {
            $firstName = explode(' ', $leadInfo['name'])[0];
            $twiml .= '<Say voice="Polly.Joanna">Hello ' . htmlspecialchars($firstName) . ', thank you for calling Boomers Hub.</Say>';
        } else {
            $twiml .= '<Say voice="Polly.Joanna">Thank you for calling Boomers Hub.</Say>';
        }
        
        if (!empty($bdmPhone)) {
            // Try to connect to BDM first
            $voicemailUrl = $siteUrl . '/index.php?module=TwilioIntegration&action=twiml&dial_action=voicemail&from=' . urlencode($from);
            
            $twiml .= '<Say voice="Polly.Joanna">Please hold while I connect you to your representative.</Say>';
            $twiml .= '<Dial timeout="20" action="' . htmlspecialchars($voicemailUrl) . '" method="POST">';
            $twiml .= '<Number>' . htmlspecialchars($bdmPhone) . '</Number>';
            $twiml .= '</Dial>';
        } else {
            // No BDM assigned, go straight to voicemail
            $this->outputVoicemailTwiML($from, $twiml);
        }
        
        $twiml .= '</Response>';
        
        echo $twiml;
    }
    
    /**
     * Handle voicemail - record message after BDM doesn't answer
     */
    private function handleVoicemail($from)
    {
        $dialStatus = isset($_REQUEST['DialCallStatus']) ? $_REQUEST['DialCallStatus'] : '';
        $siteUrl = $GLOBALS['sugar_config']['site_url'] ?? '';
        
        $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
        $twiml .= '<Response>';
        
        // Check if the BDM answered
        if ($dialStatus === 'completed') {
            // Call was answered and completed normally
            $twiml .= '<Hangup/>';
        } else {
            // BDM didn't answer - no-answer, busy, failed, etc.
            $recordingUrl = $siteUrl . '/index.php?module=TwilioIntegration&action=twiml&dial_action=recording&from=' . urlencode($from);
            
            $twiml .= '<Say voice="Polly.Joanna">I\'m sorry, your representative is not available right now. Please leave a message after the tone, and we\'ll get back to you as soon as possible.</Say>';
            $twiml .= '<Record maxLength="120" playBeep="true" action="' . htmlspecialchars($recordingUrl) . '" transcribe="true" />';
            $twiml .= '<Say voice="Polly.Joanna">I did not receive a recording. Goodbye.</Say>';
            $twiml .= '<Hangup/>';
            
            // Log missed call
            $this->logMissedCall($from);
        }
        
        $twiml .= '</Response>';
        
        echo $twiml;
    }
    
    /**
     * Handle recording completion - save voicemail to CRM
     */
    private function handleRecording()
    {
        $from = isset($_REQUEST['from']) ? $_REQUEST['from'] : (isset($_REQUEST['From']) ? $_REQUEST['From'] : '');
        $recordingUrl = isset($_REQUEST['RecordingUrl']) ? $_REQUEST['RecordingUrl'] : '';
        $recordingSid = isset($_REQUEST['RecordingSid']) ? $_REQUEST['RecordingSid'] : '';
        $recordingDuration = isset($_REQUEST['RecordingDuration']) ? $_REQUEST['RecordingDuration'] : '0';
        $transcription = isset($_REQUEST['TranscriptionText']) ? $_REQUEST['TranscriptionText'] : '';
        
        // Log voicemail
        $this->logVoicemail($from, $recordingUrl, $recordingSid, $recordingDuration, $transcription);
        
        $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
        $twiml .= '<Response>';
        $twiml .= '<Say voice="Polly.Joanna">Thank you for your message. We will call you back soon. Goodbye.</Say>';
        $twiml .= '<Hangup/>';
        $twiml .= '</Response>';
        
        echo $twiml;
    }
    
    /**
     * Handle dial status callback
     */
    private function handleDialStatus($callSid, $callStatus)
    {
        $dialCallStatus = isset($_REQUEST['DialCallStatus']) ? $_REQUEST['DialCallStatus'] : '';
        $dialCallDuration = isset($_REQUEST['DialCallDuration']) ? $_REQUEST['DialCallDuration'] : '0';
        
        // Log the call completion
        $GLOBALS['log']->info("Dial Status - CallSid: $callSid, Status: $dialCallStatus, Duration: $dialCallDuration");
        
        // Update call record in database if needed
        if (!empty($callSid)) {
            $this->updateCallRecord($callSid, $dialCallStatus, $dialCallDuration);
        }
        
        // Return empty TwiML - call is complete
        $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
        $twiml .= '<Response></Response>';
        
        echo $twiml;
    }
    
    /**
     * Helper function to output voicemail TwiML
     */
    private function outputVoicemailTwiML($from, &$twiml)
    {
        $siteUrl = $GLOBALS['sugar_config']['site_url'] ?? '';
        $recordingUrl = $siteUrl . '/index.php?module=TwilioIntegration&action=twiml&dial_action=recording&from=' . urlencode($from);
        
        $twiml .= '<Say voice="Polly.Joanna">We are currently unavailable. Please leave a message after the tone, and we\'ll get back to you as soon as possible.</Say>';
        $twiml .= '<Record maxLength="120" playBeep="true" action="' . htmlspecialchars($recordingUrl) . '" transcribe="true" />';
        $twiml .= '<Say voice="Polly.Joanna">I did not receive a recording. Goodbye.</Say>';
        $twiml .= '<Hangup/>';
    }
    
    /**
     * Clean phone number to E.164 format
     */
    private function cleanPhoneNumber($phone)
    {
        // Remove all non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        // If it doesn't start with 1 and is 10 digits, add US country code
        if (strlen($digits) === 10) {
            $digits = '1' . $digits;
        }
        
        // Return with + prefix
        return '+' . $digits;
    }
    
    /**
     * Find lead or contact by phone number
     */
    private function findLeadByPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10); // Last 10 digits
        }
        
        $db = DBManagerFactory::getInstance();
        
        // Search Leads
        $sql = "SELECT id, first_name, last_name, assigned_user_id FROM leads 
                WHERE deleted = 0 
                AND (REPLACE(REPLACE(REPLACE(REPLACE(phone_mobile, '-', ''), ' ', ''), '(', ''), ')', '') LIKE '%$phone%'
                OR REPLACE(REPLACE(REPLACE(REPLACE(phone_work, '-', ''), ' ', ''), '(', ''), ')', '') LIKE '%$phone%'
                OR REPLACE(REPLACE(REPLACE(REPLACE(phone_home, '-', ''), ' ', ''), '(', ''), ')', '') LIKE '%$phone%')
                LIMIT 1";
        
        $result = $db->query($sql);
        if ($row = $db->fetchByAssoc($result)) {
            return array(
                'id' => $row['id'],
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'type' => 'Leads',
                'assigned_user_id' => $row['assigned_user_id']
            );
        }
        
        // Search Contacts
        $sql = "SELECT id, first_name, last_name, assigned_user_id FROM contacts 
                WHERE deleted = 0 
                AND (REPLACE(REPLACE(REPLACE(REPLACE(phone_mobile, '-', ''), ' ', ''), '(', ''), ')', '') LIKE '%$phone%'
                OR REPLACE(REPLACE(REPLACE(REPLACE(phone_work, '-', ''), ' ', ''), '(', ''), ')', '') LIKE '%$phone%'
                OR REPLACE(REPLACE(REPLACE(REPLACE(phone_home, '-', ''), ' ', ''), '(', ''), ')', '') LIKE '%$phone%')
                LIMIT 1";
        
        $result = $db->query($sql);
        if ($row = $db->fetchByAssoc($result)) {
            return array(
                'id' => $row['id'],
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'type' => 'Contacts',
                'assigned_user_id' => $row['assigned_user_id']
            );
        }
        
        return null;
    }
    
    /**
     * Get BDM (assigned user) phone number
     */
    private function getBDMPhone($leadInfo)
    {
        if (!$leadInfo || empty($leadInfo['assigned_user_id'])) {
            // Return default/fallback number from config
            return $GLOBALS['sugar_config']['twilio_fallback_phone'] ?? '';
        }
        
        $db = DBManagerFactory::getInstance();
        $userId = $db->quote($leadInfo['assigned_user_id']);
        
        $sql = "SELECT phone_mobile, phone_work FROM users WHERE id = '$userId' AND deleted = 0";
        $result = $db->query($sql);
        
        if ($row = $db->fetchByAssoc($result)) {
            // Prefer mobile, fall back to work
            $phone = !empty($row['phone_mobile']) ? $row['phone_mobile'] : $row['phone_work'];
            return $this->cleanPhoneNumber($phone);
        }
        
        return '';
    }
    
    /**
     * Log missed call in CRM
     */
    private function logMissedCall($from)
    {
        $leadInfo = $this->findLeadByPhone($from);
        
        // Create a Call record
        require_once('modules/Calls/Call.php');
        $call = BeanFactory::newBean('Calls');
        
        $call->name = 'Missed Call from ' . $from;
        $call->status = 'Not Held';
        $call->direction = 'Inbound';
        $call->date_start = gmdate('Y-m-d H:i:s');
        $call->duration_hours = 0;
        $call->duration_minutes = 0;
        $call->description = "Missed inbound call from $from. Caller was sent to voicemail.";
        
        if ($leadInfo) {
            $call->parent_type = $leadInfo['type'];
            $call->parent_id = $leadInfo['id'];
            $call->assigned_user_id = $leadInfo['assigned_user_id'];
            $call->name = 'Missed Call from ' . $leadInfo['name'] . ' (' . $from . ')';
        }
        
        $call->save();
        
        // Create a Task for follow-up
        require_once('modules/Tasks/Task.php');
        $task = BeanFactory::newBean('Tasks');
        
        $task->name = 'Follow up - Missed Call from ' . $from;
        $task->status = 'Not Started';
        $task->priority = 'High';
        $task->date_due = gmdate('Y-m-d H:i:s', strtotime('+4 hours'));
        $task->description = "Missed call from $from at " . date('Y-m-d H:i:s') . ". Please call back.";
        
        if ($leadInfo) {
            $task->parent_type = $leadInfo['type'];
            $task->parent_id = $leadInfo['id'];
            $task->assigned_user_id = $leadInfo['assigned_user_id'];
            $task->name = 'Follow up - Missed Call from ' . $leadInfo['name'];
        }
        
        $task->save();
        
        $GLOBALS['log']->info("Created missed call record and follow-up task for $from");
    }
    
    /**
     * Log voicemail in CRM
     */
    private function logVoicemail($from, $recordingUrl, $recordingSid, $duration, $transcription)
    {
        $leadInfo = $this->findLeadByPhone($from);
        
        // Create a Call record for the voicemail
        require_once('modules/Calls/Call.php');
        $call = BeanFactory::newBean('Calls');
        
        $call->name = 'Voicemail from ' . $from;
        $call->status = 'Not Held';
        $call->direction = 'Inbound';
        $call->date_start = gmdate('Y-m-d H:i:s');
        $call->duration_hours = 0;
        $call->duration_minutes = intval($duration / 60);
        
        $description = "Voicemail from $from\n";
        $description .= "Duration: " . gmdate('i:s', intval($duration)) . "\n";
        $description .= "Recording: $recordingUrl\n";
        if (!empty($transcription)) {
            $description .= "\nTranscription:\n$transcription";
        }
        $call->description = $description;
        
        if ($leadInfo) {
            $call->parent_type = $leadInfo['type'];
            $call->parent_id = $leadInfo['id'];
            $call->assigned_user_id = $leadInfo['assigned_user_id'];
            $call->name = 'Voicemail from ' . $leadInfo['name'];
        }
        
        $call->save();
        
        // Create high priority task
        require_once('modules/Tasks/Task.php');
        $task = BeanFactory::newBean('Tasks');
        
        $task->name = 'Listen to voicemail from ' . $from;
        $task->status = 'Not Started';
        $task->priority = 'High';
        $task->date_due = gmdate('Y-m-d H:i:s', strtotime('+2 hours'));
        
        $taskDesc = "New voicemail received from $from\n";
        $taskDesc .= "Listen to recording: $recordingUrl\n";
        if (!empty($transcription)) {
            $taskDesc .= "\nTranscription:\n$transcription";
        }
        $task->description = $taskDesc;
        
        if ($leadInfo) {
            $task->parent_type = $leadInfo['type'];
            $task->parent_id = $leadInfo['id'];
            $task->assigned_user_id = $leadInfo['assigned_user_id'];
            $task->name = 'Listen to voicemail from ' . $leadInfo['name'];
        }
        
        $task->save();
        
        $GLOBALS['log']->info("Logged voicemail from $from - Recording: $recordingSid");
    }
    
    /**
     * Update call record with completion status
     */
    private function updateCallRecord($callSid, $status, $duration)
    {
        // Try to find and update the call record
        $db = DBManagerFactory::getInstance();
        $callSidSafe = $db->quote($callSid);
        
        $sql = "SELECT id FROM calls WHERE description LIKE '%$callSidSafe%' AND deleted = 0 ORDER BY date_entered DESC LIMIT 1";
        $result = $db->query($sql);
        
        if ($row = $db->fetchByAssoc($result)) {
            $call = BeanFactory::getBean('Calls', $row['id']);
            if ($call) {
                $call->status = ($status === 'completed') ? 'Held' : 'Not Held';
                $durationInt = intval($duration);
                $call->duration_hours = floor($durationInt / 3600);
                $call->duration_minutes = floor(($durationInt % 3600) / 60);
                $call->description .= "\n\nCall completed - Status: $status, Duration: " . gmdate('H:i:s', $durationInt);
                $call->save();
                
                $GLOBALS['log']->info("Updated call record {$row['id']} - Status: $status, Duration: $duration");
            }
        }
    }
}
