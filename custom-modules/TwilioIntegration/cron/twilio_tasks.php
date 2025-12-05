<?php
/**
 * Twilio Scheduled Tasks
 * Run this file via cron every hour
 *
 * Example crontab entry:
 * 0 * * * * cd /path/to/suitecrm && php custom/modules/TwilioIntegration/cron/twilio_tasks.php > /dev/null 2>&1
 */

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

// Bootstrap SuiteCRM
chdir(dirname(__FILE__) . '/../../../../');
require_once('include/entryPoint.php');
require_once('modules/TwilioIntegration/TwilioScheduler.php');

$GLOBALS['log']->info("======= Twilio Scheduled Tasks Started =======");

try {
    // Task 1: Check for unreplied SMS messages
    $GLOBALS['log']->info("Task 1: Checking unreplied SMS...");
    $smsCount = TwilioScheduler::checkUnrepliedSMS();
    $GLOBALS['log']->info("Task 1 Complete: Created $smsCount follow-up tasks");

    // Task 2: Clean up old recordings (run once per day)
    $hour = date('H');
    if ($hour == '02') { // Run at 2 AM
        $GLOBALS['log']->info("Task 2: Cleaning up old recordings...");
        $deletedCount = TwilioScheduler::cleanupOldRecordings();
        $GLOBALS['log']->info("Task 2 Complete: Deleted $deletedCount old recordings");
    }

    // Task 3: Generate daily summary (run at midnight)
    if ($hour == '00') {
        $GLOBALS['log']->info("Task 3: Generating daily summary...");
        $summary = TwilioScheduler::generateActivitySummary('daily');
        $GLOBALS['log']->info("Task 3 Complete: Summary generated");
    }

    $GLOBALS['log']->info("======= Twilio Scheduled Tasks Completed =======");

} catch (Exception $e) {
    $GLOBALS['log']->error("Twilio Scheduled Tasks Error: " . $e->getMessage());
    $GLOBALS['log']->error($e->getTraceAsString());
}
