<?php
/**
 * Twilio Scheduler
 * Handles scheduled tasks like SMS follow-ups
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class TwilioScheduler
{
    /**
     * Check for unreplied SMS messages and create follow-up tasks
     * Should be run via cron job every hour
     */
    public static function checkUnrepliedSMS()
    {
        global $sugar_config;

        // Get follow-up threshold from config (default: 24 hours)
        $followUpHours = $sugar_config['twilio_sms_followup_hours'] ?? 24;

        $GLOBALS['log']->info("TwilioScheduler: Checking for unreplied SMS (threshold: {$followUpHours}h)");

        $db = DBManagerFactory::getInstance();

        // Calculate cutoff time
        $cutoffTime = gmdate('Y-m-d H:i:s', strtotime("-{$followUpHours} hours"));

        // Find inbound SMS (notes) that haven't been replied to
        $sql = "SELECT n.id, n.name, n.description, n.parent_type, n.parent_id,
                       n.assigned_user_id, n.date_entered,
                       SUBSTRING_INDEX(SUBSTRING(n.description, LOCATE('From:', n.description) + 5), '\n', 1) as phone_from
                FROM notes n
                WHERE n.deleted = 0
                AND (n.name LIKE '%📥 SMS from%' OR n.name LIKE 'SMS from%')
                AND n.date_entered >= '$cutoffTime'
                AND n.date_entered <= '" . gmdate('Y-m-d H:i:s', strtotime("-{$followUpHours} hours")) . "'
                AND n.id NOT IN (
                    -- Exclude if there's a follow-up task already created
                    SELECT t.parent_id FROM tasks t
                    WHERE t.deleted = 0
                    AND t.parent_type = 'Notes'
                    AND t.name LIKE '%Reply to SMS%'
                    AND t.parent_id = n.id
                )
                AND n.parent_id NOT IN (
                    -- Exclude if there's been an outbound SMS response
                    SELECT n2.parent_id FROM notes n2
                    WHERE n2.deleted = 0
                    AND n2.parent_type = n.parent_type
                    AND n2.parent_id = n.parent_id
                    AND (n2.name LIKE '%📤%' OR n2.name LIKE 'SMS to%')
                    AND n2.date_entered > n.date_entered
                )";

        $result = $db->query($sql);
        $count = 0;

        while ($row = $db->fetchByAssoc($result)) {
            // Create high-priority follow-up task
            $task = BeanFactory::newBean('Tasks');

            $phoneFrom = trim($row['phone_from'] ?? '');
            $smsSubject = $row['name'] ?? 'Unknown';

            $task->name = "URGENT: Follow up on unreplied SMS";
            $task->status = 'Not Started';
            $task->priority = 'High';
            $task->date_due = gmdate('Y-m-d H:i:s', strtotime('+2 hours'));

            $taskDesc = "⚠️ SMS HAS NOT BEEN REPLIED TO FOR {$followUpHours}+ HOURS\n\n";
            $taskDesc .= "Original SMS: $smsSubject\n";
            $taskDesc .= "From: $phoneFrom\n";
            $taskDesc .= "Received: " . $row['date_entered'] . "\n\n";
            $taskDesc .= "Please respond immediately to maintain customer engagement.\n\n";
            $taskDesc .= "View original SMS: [Note ID: " . $row['id'] . "]";

            $task->description = $taskDesc;

            if (!empty($row['parent_type']) && !empty($row['parent_id'])) {
                $task->parent_type = $row['parent_type'];
                $task->parent_id = $row['parent_id'];
            }

            if (!empty($row['assigned_user_id'])) {
                $task->assigned_user_id = $row['assigned_user_id'];
            }

            // Link to the SMS note
            $task->save();

            // Optionally send email notification
            if ($sugar_config['twilio_sms_followup_email'] ?? true) {
                self::sendFollowUpEmailNotification($row, $task->id);
            }

            $count++;
            $GLOBALS['log']->info("TwilioScheduler: Created follow-up task for unreplied SMS [Note: {$row['id']}]");
        }

        $GLOBALS['log']->info("TwilioScheduler: Completed SMS follow-up check - Created $count tasks");

        return $count;
    }

    /**
     * Send email notification for unreplied SMS
     */
    private static function sendFollowUpEmailNotification($smsData, $taskId)
    {
        try {
            if (empty($smsData['assigned_user_id'])) {
                return;
            }

            $user = BeanFactory::getBean('Users', $smsData['assigned_user_id']);

            if (!$user || empty($user->email1)) {
                return;
            }

            global $sugar_config;
            $fromEmail = $sugar_config['notify_fromaddress'] ?? 'noreply@boomershub.com';
            $fromName = $sugar_config['notify_fromname'] ?? 'Boomers Hub CRM';

            $subject = "⚠️ URGENT: Unreplied SMS Requires Follow-up";

            $body = "Hello {$user->first_name},\n\n";
            $body .= "An SMS message has not been replied to and requires your immediate attention.\n\n";
            $body .= "SMS Subject: {$smsData['name']}\n";
            $body .= "Received: {$smsData['date_entered']}\n\n";
            $body .= "A high-priority task has been created for you.\n\n";
            $body .= "Please log in to the CRM and respond to maintain customer engagement.\n\n";
            $body .= "---\n";
            $body .= "This is an automated notification from Twilio Integration.";

            // Use SuiteCRM's email sending mechanism
            require_once('include/SugarPHPMailer.php');

            $mail = new SugarPHPMailer();
            $mail->setMailerForSystem();
            $mail->From = $fromEmail;
            $mail->FromName = $fromName;
            $mail->addAddress($user->email1);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);

            $sent = $mail->send();

            if ($sent) {
                $GLOBALS['log']->info("TwilioScheduler: Sent follow-up email to {$user->email1}");
            } else {
                $GLOBALS['log']->warn("TwilioScheduler: Failed to send follow-up email");
            }

        } catch (Exception $e) {
            $GLOBALS['log']->error("TwilioScheduler: Email notification error - " . $e->getMessage());
        }
    }

    /**
     * Clean up old recordings (optional maintenance task)
     * Removes recordings older than configured retention period
     */
    public static function cleanupOldRecordings()
    {
        global $sugar_config;

        $retentionDays = $sugar_config['twilio_recording_retention_days'] ?? 365;

        $GLOBALS['log']->info("TwilioScheduler: Cleaning up recordings older than $retentionDays days");

        $recordingPath = $sugar_config['twilio_recording_path'] ?? 'upload/twilio_recordings';

        if (strpos($recordingPath, 'upload://') === 0) {
            $recordingPath = str_replace('upload://', 'upload/', $recordingPath);
        }

        if (!is_dir($recordingPath)) {
            return 0;
        }

        $cutoffDate = strtotime("-{$retentionDays} days");
        $deletedCount = 0;

        $files = glob($recordingPath . '/recording_*.mp3');

        foreach ($files as $file) {
            $fileTime = filemtime($file);

            if ($fileTime < $cutoffDate) {
                if (unlink($file)) {
                    $deletedCount++;
                    $GLOBALS['log']->info("TwilioScheduler: Deleted old recording: " . basename($file));
                }
            }
        }

        $GLOBALS['log']->info("TwilioScheduler: Cleanup complete - Deleted $deletedCount recordings");

        return $deletedCount;
    }

    /**
     * Generate daily/weekly activity summary
     */
    public static function generateActivitySummary($period = 'daily')
    {
        $db = DBManagerFactory::getInstance();

        $dateFilter = $period === 'daily'
            ? gmdate('Y-m-d 00:00:00')
            : gmdate('Y-m-d 00:00:00', strtotime('-7 days'));

        // Get call stats
        $callSql = "SELECT
                        COUNT(*) as total_calls,
                        SUM(CASE WHEN direction = 'Outbound' THEN 1 ELSE 0 END) as outbound,
                        SUM(CASE WHEN direction = 'Inbound' THEN 1 ELSE 0 END) as inbound,
                        SUM(CASE WHEN status = 'Held' THEN 1 ELSE 0 END) as connected,
                        SUM(CASE WHEN status = 'Not Held' THEN 1 ELSE 0 END) as missed
                    FROM calls
                    WHERE deleted = 0 AND date_start >= '$dateFilter'";

        $callResult = $db->query($callSql);
        $callData = $db->fetchByAssoc($callResult);

        // Get SMS stats
        $smsSql = "SELECT
                       COUNT(*) as total_sms,
                       SUM(CASE WHEN name LIKE '%📤%' THEN 1 ELSE 0 END) as sent,
                       SUM(CASE WHEN name LIKE '%📥%' THEN 1 ELSE 0 END) as received
                   FROM notes
                   WHERE deleted = 0
                   AND (name LIKE '%SMS%')
                   AND date_entered >= '$dateFilter'";

        $smsResult = $db->query($smsSql);
        $smsData = $db->fetchByAssoc($smsResult);

        $summary = [
            'period' => $period,
            'date_from' => $dateFilter,
            'calls' => $callData,
            'sms' => $smsData,
            'generated_at' => gmdate('Y-m-d H:i:s')
        ];

        $GLOBALS['log']->info("TwilioScheduler: Generated $period activity summary");

        return $summary;
    }
}
