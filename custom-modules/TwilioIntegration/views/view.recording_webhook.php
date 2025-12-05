<?php
/**
 * Twilio Recording Webhook Handler
 * Handles recording status callbacks from Twilio
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/TwilioIntegration/TwilioSecurity.php');
require_once('modules/TwilioIntegration/TwilioRecordingManager.php');

class TwilioIntegrationViewRecording_webhook extends SugarView
{
    public function display()
    {
        // Validate webhook signature for security
        TwilioSecurity::validateOrDie('recording_webhook');

        $recordingSid = $_REQUEST['RecordingSid'] ?? '';
        $callSid = $_REQUEST['CallSid'] ?? '';
        $status = $_REQUEST['RecordingStatus'] ?? '';

        $GLOBALS['log']->info("Recording Webhook - SID: $recordingSid, Call: $callSid, Status: $status");

        // Process the recording
        TwilioRecordingManager::handleRecordingWebhook();

        // Return 200 OK
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'processed' => true]);

        die();
    }
}
